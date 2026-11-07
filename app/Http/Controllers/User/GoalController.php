<?php

namespace App\Http\Controllers\User;

use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveGoalRequest;
use App\Models\Goal;
use App\Support\Rupiah;
use App\Support\UserClock;
use App\Support\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoalController extends Controller
{
    public function index(Request $request, UserClock $clock, \App\Services\GoalProgress $progress)
    {
        $this->authorize('viewAny', Goal::class);
        $goals = $request->user()->goals()->where('status', GoalStatus::Active)
            ->with(['updates', 'habit', 'wallet', 'user'])->orderBy('due_on')->get();
        $goals->each(function (Goal $goal) use ($progress) {
            $current = $progress->current($goal);
            $goal->setAttribute('current_value', $current);
            $goal->setAttribute('progress_percent', $progress->percent($goal, $current));
            $goal->setAttribute('remaining_per_day', $progress->remainingPerDay($goal, $current));
        });
        $today = $clock->date($request->user());
        $habits = $request->user()->habits()->whereNull('archived_at')->orderBy('title')->get();
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $goal = new Goal;

        return view('user.goals.index', compact('goals', 'today', 'habits', 'wallets', 'goal'));
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
        $request->user()->goals()->create($data);

        return Feedback::success(back(), 'Target berhasil ditambahkan.');
    }

    public function edit(Request $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        return view('user.goals.edit', [
            'goal' => $goal,
            'habits' => $request->user()->habits()->whereNull('archived_at')->orderBy('title')->get(),
            'wallets' => $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveGoalRequest $request, Goal $goal)
    {
        $data = $request->validated();
        if ($data['type'] !== \App\Enums\GoalType::Habit->value) {
            $data['habit_id'] = null;
        }
        if ($data['type'] !== \App\Enums\GoalType::Savings->value) {
            $data['wallet_id'] = null;
        }
        $goal->update($data);

        return Feedback::success(redirect()->route('user.goals.index'), 'Target berhasil diperbarui.');
    }

    public function updateProgress(Request $request, Goal $goal, UserClock $clock)
    {
        $this->authorize('update', $goal);
        abort_if($goal->user_id !== $request->user()->id || $goal->status !== GoalStatus::Active, 404);
        abort_unless($goal->type === \App\Enums\GoalType::Number, 422, 'Progres target ini berasal dari kebiasaan atau saldo dompet.');
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'recorded_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$goal->created_at->timezone($request->user()->timezone)->toDateString(),
                'before_or_equal:'.$clock->date($request->user()),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($goal, $request, $data) {
            $locked = $request->user()->goals()->lockForUpdate()->findOrFail($goal->id);
            $current = $locked->start_value + (int) $locked->updates()->sum('amount');
            abort_if($data['amount'] > $locked->target_value - $current, 422, 'Nilai progres melebihi sisa target.');
            $request->user()->goalUpdates()->create([
                'goal_id' => $locked->id,
                'amount' => $data['amount'],
                'recorded_on' => $data['recorded_on'],
                'note' => $data['note'] ?? null,
            ]);
        });

        return Feedback::success(back(), 'Progres target berhasil diperbarui.');
    }

    public function markAchieved(Goal $goal, \App\Services\GoalProgress $progress)
    {
        $this->authorize('update', $goal);
        abort_unless($progress->percent($goal) >= 100, 422, 'Target belum mencapai nilai yang ditentukan.');
        $goal->update(['status' => GoalStatus::Achieved]);

        return Feedback::success(back(), 'Target ditandai tercapai.');
    }

    public function archive(Goal $goal)
    {
        $this->authorize('update', $goal);
        $goal->update(['status' => GoalStatus::Archived]);

        return Feedback::success(back(), 'Target diarsipkan.');
    }
}
