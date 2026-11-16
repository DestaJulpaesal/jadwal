<?php

namespace App\Http\Controllers\User;

use App\Enums\GoalDirection;
use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveGoalRequest;
use App\Models\Goal;
use App\Models\GoalUpdate;
use App\Support\Db;
use App\Support\Feedback;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function index(Request $request, UserClock $clock, \App\Services\GoalProgress $progress)
    {
        $this->authorize('viewAny', Goal::class);
        $statusOptions = [
            GoalStatus::Active->value => GoalStatus::Active->label(),
            GoalStatus::Achieved->value => GoalStatus::Achieved->label(),
            GoalStatus::Archived->value => GoalStatus::Archived->label(),
        ];
        $status = $request->validate([
            'status' => ['nullable', Rule::in(array_keys($statusOptions))],
        ])['status'] ?? GoalStatus::Active->value;
        $goals = $request->user()->goals()->where('status', $status)
            ->with([
                'updates' => fn ($query) => $query->orderBy('recorded_on')->orderBy('id'),
                'habit',
                'wallet',
                'user',
            ])->orderBy('due_on')->get();
        $goals->each(function (Goal $goal) use ($progress) {
            $current = $progress->current($goal);
            $goal->setAttribute('current_value', $current);
            $goal->setAttribute('remaining_value', $progress->remaining($goal, $current));
            $goal->setAttribute('progress_percent', $progress->percent($goal, $current));
            $goal->setAttribute('remaining_per_day', $progress->remainingPerDay($goal, $current));
        });
        $today = $clock->date($request->user());
        $habits = $request->user()->habits()->whereNull('archived_at')->orderBy('title')->get();
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $goal = new Goal;

        return view('user.goals.index', compact('goals', 'today', 'habits', 'wallets', 'goal', 'status', 'statusOptions'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Goal::class);

        return view('user.goals.create', [
            'goal' => new Goal,
            'habits' => $request->user()->habits()->whereNull('archived_at')->orderBy('title')->get(),
            'wallets' => $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveGoalRequest $request)
    {
        $data = $request->validated();
        if ($data['type'] !== \App\Enums\GoalType::Habit->value) {
            $data['habit_id'] = null;
        }
        if ($data['type'] !== \App\Enums\GoalType::Savings->value) {
            $data['wallet_id'] = null;
        }
        $data['direction'] = $data['direction'] ?? GoalDirection::Increase->value;
        $data['start_value'] = (int) ($data['start_value'] ?? 0);
        $request->user()->goals()->create($data);

        return Feedback::success(back(), __('ui.feedback.goal_created'));
    }

    public function edit(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);
        abort_if($goal->status === GoalStatus::Archived, 404);

        return view('user.goals.edit', [
            'goal' => $goal,
            'habits' => $request->user()->habits()->whereNull('archived_at')->orderBy('title')->get(),
            'wallets' => $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveGoalRequest $request, Goal $goal)
    {
        abort_if($goal->status === GoalStatus::Archived, 404);
        $data = $request->validated();
        if ($data['type'] !== \App\Enums\GoalType::Habit->value) {
            $data['habit_id'] = null;
        }
        if ($data['type'] !== \App\Enums\GoalType::Savings->value) {
            $data['wallet_id'] = null;
        }
        if (array_key_exists('start_value', $data)) {
            $data['start_value'] = (int) ($data['start_value'] ?? 0);
        }
        $goal->update($data);

        return Feedback::success(redirect()->route('user.goals.index'), __('ui.feedback.goal_updated'));
    }

    public function updateProgress(Request $request, Goal $goal, UserClock $clock)
    {
        $this->authorize('update', $goal);
        abort_if($goal->user_id !== $request->user()->id || $goal->status !== GoalStatus::Active, 404);
        abort_unless($goal->type === \App\Enums\GoalType::Number, 422, __('ui.feedback.goal_progress_type'));
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'between:-'.Rupiah::MAX_AMOUNT.','.Rupiah::MAX_AMOUNT, 'not_in:0'],
            'recorded_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$goal->created_at->timezone($request->user()->timezone)->toDateString(),
                'before_or_equal:'.$clock->date($request->user()),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        Db::immediate(function () use ($goal, $request, $data) {
            $locked = $request->user()->goals()->lockForUpdate()->findOrFail($goal->id);
            $current = $locked->start_value + (int) $locked->updates()->sum('amount');
            $next = $current + $data['amount'];
            abort_if($next < 0, 422, __('ui.feedback.goal_progress_below_zero'));
            abort_if($next > Rupiah::MAX_AMOUNT, 422, __('ui.feedback.goal_progress_exceeds_remaining'));
            abort_if(
                ($locked->direction === GoalDirection::Increase && $data['amount'] > 0 && $next > $locked->target_value)
                    || ($locked->direction === GoalDirection::Decrease && $data['amount'] < 0 && $next < $locked->target_value),
                422,
                __('ui.feedback.goal_progress_exceeds_remaining')
            );
            $request->user()->goalUpdates()->create([
                'goal_id' => $locked->id,
                'amount' => $data['amount'],
                'recorded_on' => $data['recorded_on'],
                'note' => $data['note'] ?? null,
            ]);
        });

        return Feedback::success(back(), __('ui.feedback.goal_progress_updated'));
    }

    public function destroyUpdate(Request $request, Goal $goal, GoalUpdate $goalUpdate, \App\Services\GoalProgress $progress)
    {
        $this->authorize('update', $goal);
        $this->authorize('delete', $goalUpdate);
        abort_if(
            $goal->user_id !== $request->user()->id
                || $goalUpdate->user_id !== $request->user()->id
                || $goalUpdate->goal_id !== $goal->id
                || $goal->type !== \App\Enums\GoalType::Number
                || $goal->status === GoalStatus::Archived,
            404
        );
        Db::immediate(function () use ($request, $goal, $goalUpdate, $progress) {
            $lockedGoal = $request->user()->goals()->lockForUpdate()->findOrFail($goal->id);
            $lockedUpdate = $request->user()->goalUpdates()->lockForUpdate()->findOrFail($goalUpdate->id);
            abort_unless($lockedUpdate->goal_id === $lockedGoal->id, 404);
            $latestUpdate = $lockedGoal->updates()->orderByDesc('id')->firstOrFail();
            abort_unless($latestUpdate->id === $lockedUpdate->id, 422, __('ui.feedback.goal_update_not_latest'));
            $lockedUpdate->delete();

            if ($lockedGoal->status === GoalStatus::Achieved && $progress->percent($lockedGoal) < 100) {
                $lockedGoal->update(['status' => GoalStatus::Active]);
            }
        });

        return Feedback::success(back(), __('ui.feedback.goal_progress_undone'));
    }

    public function markAchieved(Goal $goal, \App\Services\GoalProgress $progress)
    {
        $this->authorize('update', $goal);
        abort_unless($goal->status === GoalStatus::Active, 404);
        abort_unless($progress->percent($goal) >= 100, 422, __('ui.feedback.goal_not_reached'));
        $goal->update(['status' => GoalStatus::Achieved]);

        return Feedback::success(back(), __('ui.feedback.goal_achieved'));
    }

    public function archive(Goal $goal)
    {
        $this->authorize('update', $goal);
        abort_if($goal->status === GoalStatus::Archived, 404);
        $goal->update(['status' => GoalStatus::Archived]);

        return Feedback::success(back(), __('ui.feedback.goal_archived'));
    }

    public function restore(Goal $goal)
    {
        $this->authorize('update', $goal);
        abort_unless($goal->status === GoalStatus::Archived, 404);
        $goal->update(['status' => GoalStatus::Active]);

        return Feedback::success(back(), __('ui.feedback.record_restored'));
    }

    public function destroy(Goal $goal)
    {
        $this->authorize('delete', $goal);
        abort_unless($goal->status === GoalStatus::Archived, 404);
        Db::immediate(fn () => $goal->delete());

        return Feedback::success(back(), __('ui.feedback.record_deleted'));
    }
}
