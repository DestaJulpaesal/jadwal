<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkTaskRequest;
use App\Http\Requests\SaveTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\TaskCompletion;
use App\Services\TaskRecurrence;
use App\Support\Db as DatabaseTransaction;
use App\Support\Feedback;
use App\Support\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        private TaskCompletion $taskCompletion,
        private TaskRecurrence $taskRecurrence
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status_id' => ['nullable', 'integer', 'exists:task_statuses,id'],
            'due' => ['nullable', Rule::in(['today', 'upcoming', 'overdue', 'none'])],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id)
                ),
            ],
            'sort' => ['nullable', Rule::in(['due', 'created', 'title'])],
        ]);
        $query = auth()->user()->tasks()->with(['category', 'status']);

        if (! empty($filters['search'])) {
            $pattern = '%'.Like::escape($filters['search']).'%';
            $query->where(function ($query) use ($pattern) {
                $query->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('task_status_id', $filters['status_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $todayStartLocal = Carbon::now($request->user()->timezone)->startOfDay();
        $todayStart = $todayStartLocal->copy()->utc();
        $tomorrowStart = $todayStartLocal->copy()->addDay()->utc();
        $nowUtc = Carbon::now('UTC');

        match ($filters['due'] ?? null) {
            'today' => $query->where('due_date', '>=', $todayStart)
                ->where('due_date', '<', $tomorrowStart),
            'upcoming' => $query->where('due_date', '>=', $tomorrowStart),
            'overdue' => $query
                ->whereDoesntHave('status', fn ($query) => $query->where('is_completed', true))
                ->where(function ($query) use ($nowUtc) {
                    $query->where(fn ($query) => $query->whereNotNull('due_date')->where('due_date', '<', $nowUtc))
                        ->orWhere(fn ($query) => $query->whereNull('due_date')->whereNotNull('ends_at')->where('ends_at', '<', $nowUtc));
                }),
            'none' => $query->whereNull('due_date'),
            default => null,
        };

        $sort = $filters['sort'] ?? 'due';
        $query->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM task_statuses WHERE task_statuses.id = tasks.task_status_id AND task_statuses.is_completed = 1) THEN 1 ELSE 0 END');
        match ($sort) {
            'created' => $query->orderByDesc('tasks.created_at'),
            'title' => $query->orderBy('tasks.title'),
            default => $query->orderByRaw('CASE WHEN tasks.due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('tasks.due_date')
                ->orderByDesc('tasks.created_at'),
        };
        $tasks = $query->orderBy('tasks.id')->paginate(10)->withQueryString();
        $statuses = TaskStatus::orderBy('name')->get();
        $categories = $this->availableCategories($request->user());
        $completedStatus = $statuses->firstWhere('is_completed', true);
        $counts = $request->user()->tasks()
            ->selectRaw(
                'COUNT(*) AS all_count,
                COALESCE(SUM(CASE WHEN due_date >= ? AND due_date < ? THEN 1 ELSE 0 END), 0) AS today_count,
                COALESCE(SUM(CASE WHEN due_date >= ? THEN 1 ELSE 0 END), 0) AS upcoming_count,
                COALESCE(SUM(CASE
                    WHEN NOT EXISTS (
                        SELECT 1 FROM task_statuses
                        WHERE task_statuses.id = tasks.task_status_id
                            AND task_statuses.is_completed = 1
                    ) AND (
                        (due_date IS NOT NULL AND due_date < ?)
                        OR (due_date IS NULL AND ends_at IS NOT NULL AND ends_at < ?)
                    )
                    THEN 1 ELSE 0
                END), 0) AS overdue_count,
                COALESCE(SUM(CASE WHEN task_status_id = ? THEN 1 ELSE 0 END), 0) AS completed_count',
                [$todayStart, $tomorrowStart, $tomorrowStart, $nowUtc, $nowUtc, $completedStatus?->id ?? 0]
            )
            ->first();
        $taskCounts = [
            'all' => (int) $counts->all_count,
            'today' => (int) $counts->today_count,
            'upcoming' => (int) $counts->upcoming_count,
            'overdue' => (int) $counts->overdue_count,
            'completed' => (int) $counts->completed_count,
        ];

        return view('user.tasks.index', compact('tasks', 'statuses', 'categories', 'filters', 'taskCounts', 'completedStatus'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Task::class);

        return view('user.tasks.create', $this->formData($request->user()));
    }

    public function store(SaveTaskRequest $request)
    {
        $validated = $this->normalizeRecurrence($request->validated(), $request->user());
        $validated = $this->normalizeScheduleTimes($validated, $request->user());
        $statusId = $validated['task_status_id'] ?? null;
        $completedAt = $this->taskCompletion->forStatusTransition(
            false,
            $this->isCompletedStatus($statusId),
            null
        );

        $result = DB::transaction(function () use ($validated, $request, $completedAt): array {
            $category = $this->resolveCategory($validated, $request);

            $task = $request->user()->tasks()->create([
                'category_id' => $category?->id,
                'task_status_id' => $validated['task_status_id'] ?? null,
                'completed_at' => $completedAt,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'recurrence' => $validated['recurrence'],
                'recurrence_days' => $validated['recurrence_days'],
                'recurrence_until' => $validated['recurrence_until'],
                'recurrence_anchor_day' => $validated['recurrence_anchor_day'],
            ]);

            $recurrenceStopReason = null;
            if ($completedAt !== null) {
                if ($this->taskRecurrence->createNext($task, $request->user()) === null) {
                    $recurrenceStopReason = $this->taskRecurrence->stopReason($task, $request->user());
                }
            }

            return ['task' => $task, 'recurrence_stop_reason' => $recurrenceStopReason];
        });
        $task = $result['task'];
        $message = $this->recurrenceStopMessage($result['recurrence_stop_reason'], $task)
            ?? __('ui.feedback.task_created');

        return Feedback::success(
            redirect()->route('user.tasks.index')->with('schedule_conflicts', $this->scheduleConflicts($task)),
            $message
        );
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        return view('user.tasks.edit', array_merge($this->formData($task->user), compact('task')));
    }

    public function update(SaveTaskRequest $request, Task $task)
    {
        $validated = $this->normalizeRecurrence($request->validated(), $request->user(), $task);
        $validated = $this->normalizeScheduleTimes($validated, $request->user());
        $wasCompleted = $task->status()->where('is_completed', true)->exists();
        $isCompleted = $this->isCompletedStatus($validated['task_status_id'] ?? null);
        $completedAt = $this->taskCompletion->forStatusTransition($wasCompleted, $isCompleted, $task->completed_at);

        $recurrenceStopReason = DB::transaction(function () use ($validated, $request, $task, $completedAt, $wasCompleted, $isCompleted): ?string {
            $category = $this->resolveCategory($validated, $request);

            $task->update([
                'category_id' => $category?->id,
                'task_status_id' => $validated['task_status_id'] ?? null,
                'completed_at' => $completedAt,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'recurrence' => $validated['recurrence'],
                'recurrence_days' => $validated['recurrence_days'],
                'recurrence_until' => $validated['recurrence_until'],
                'recurrence_anchor_day' => $validated['recurrence_anchor_day'],
            ]);

            if (! $wasCompleted && $isCompleted) {
                $completedTask = $task->fresh();
                if ($this->taskRecurrence->createNext($completedTask, $request->user()) === null) {
                    return $this->taskRecurrence->stopReason($completedTask, $request->user());
                }
            }

            return null;
        });
        $message = $this->recurrenceStopMessage($recurrenceStopReason, $task)
            ?? __('ui.feedback.task_updated');

        return Feedback::success(
            redirect()->route('user.tasks.index')->with('schedule_conflicts', $this->scheduleConflicts($task->refresh())),
            $message
        );
    }

    public function complete(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $completedStatus = TaskStatus::where('is_completed', true)->first();

        if (! $completedStatus) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.feedback.task_status_missing')], 422);
            }

            return Feedback::error(back(), __('ui.feedback.task_status_missing'));
        }

        $result = DatabaseTransaction::immediate(function () use ($task, $request, $completedStatus): array {
            $task = $request->user()->tasks()->lockForUpdate()->findOrFail($task->id);

            if ($task->status()->where('is_completed', true)->exists()) {
                return ['already_completed' => true];
            }

            $task->update([
                'task_status_id' => $completedStatus->id,
                'completed_at' => $this->taskCompletion->forStatusTransition(false, true, null),
            ]);
            $completedTask = $task->fresh();
            $nextTask = $this->taskRecurrence->createNext($completedTask, $request->user());

            return [
                'already_completed' => false,
                'recurrence_stop_reason' => $nextTask === null
                    ? $this->taskRecurrence->stopReason($completedTask, $request->user())
                    : null,
            ];
        });

        if ($result['already_completed']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.feedback.task_already_completed')]);
            }

            return Feedback::info(back(), __('ui.feedback.task_already_completed'));
        }

        $message = $this->recurrenceStopMessage($result['recurrence_stop_reason'], $task);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?? __('ui.feedback.task_completed'),
                'status' => $completedStatus->name,
            ]);
        }

        return Feedback::success(back(), $message ?? __('ui.feedback.task_completed'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        DB::transaction(fn () => $this->deleteTaskOnly($task, auth()->user()));

        return Feedback::success(redirect()->route('user.tasks.index'), __('ui.feedback.task_deleted'));
    }

    public function destroySeries(Task $task)
    {
        $this->authorize('delete', $task);

        DB::transaction(function () use ($task): void {
            $user = auth()->user();
            $seriesId = $task->recurrence_parent_id ?? $task->id;

            $user->tasks()
                ->where(function ($query) use ($seriesId): void {
                    $query->whereKey($seriesId)->orWhere('recurrence_parent_id', $seriesId);
                })
                ->update([
                    'recurrence' => 'none',
                    'recurrence_days' => null,
                    'recurrence_until' => null,
                ]);

            $user->tasks()->whereKey($task->id)->delete();
        });

        return Feedback::success(
            redirect()->route('user.tasks.index'),
            __('ui.feedback.task_recurrence_stopped')
        );
    }

    public function bulk(BulkTaskRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $taskIds = array_map('intval', $validated['task_ids']);
        $action = $validated['action'];
        $status = $action === 'complete'
            ? TaskStatus::query()->where('is_completed', true)->first()
            : ($action === 'status' ? TaskStatus::query()->findOrFail($validated['task_status_id']) : null);

        if ($action === 'complete' && ! $status) {
            return Feedback::error(
                redirect()->route('user.tasks.index'),
                __('ui.feedback.task_status_missing')
            );
        }

        $recurrenceStopReasons = [];
        DatabaseTransaction::immediate(function () use ($user, $taskIds, $action, $status, &$recurrenceStopReasons): void {
            $tasks = $user->tasks()->whereIn('id', $taskIds)->orderBy('id')->lockForUpdate()->get();
            if ($tasks->count() !== count(array_unique($taskIds))) {
                abort(404);
            }

            foreach ($tasks as $task) {
                $this->authorize($action === 'delete' ? 'delete' : 'update', $task);

                if ($action === 'delete') {
                    $this->deleteTaskOnly($task, $user, $taskIds);

                    continue;
                }

                $wasCompleted = $task->status()->where('is_completed', true)->exists();
                $isCompleted = $status->is_completed;
                if ($action === 'complete' && $wasCompleted) {
                    continue;
                }

                $task->update([
                    'task_status_id' => $status->id,
                    'completed_at' => $this->taskCompletion->forStatusTransition(
                        $wasCompleted,
                        $isCompleted,
                        $task->completed_at
                    ),
                ]);

                if (! $wasCompleted && $isCompleted) {
                    $completedTask = $task->fresh();
                    if ($this->taskRecurrence->createNext($completedTask, $user) === null) {
                        $reason = $this->taskRecurrence->stopReason($completedTask, $user);
                        if ($reason !== null) {
                            $recurrenceStopReasons[] = $reason;
                        }
                    }
                }
            }
        });

        $message = $action === 'delete'
            ? __('ui.feedback.task_bulk_deleted')
            : __('ui.feedback.task_bulk_updated');
        if ($recurrenceStopReasons !== []) {
            $message .= ' '.__('ui.feedback.task_bulk_recurrence_stopped', [
                'count' => count($recurrenceStopReasons),
            ]);
        }

        return Feedback::success(
            redirect()->route('user.tasks.index'),
            $message
        );
    }

    private function formData(User $user): array
    {
        return [
            'categories' => $this->availableCategories($user),
            'statuses' => TaskStatus::orderBy('name')->get(),
            'recurrenceOptions' => [
                'none' => __('ui.task.recurrence.none'),
                'daily' => __('ui.task.recurrence.daily'),
                'weekly' => __('ui.task.recurrence.weekly'),
                'monthly' => __('ui.task.recurrence.monthly'),
            ],
            'weekdays' => [
                1 => __('ui.weekdays.monday_short'),
                2 => __('ui.weekdays.tuesday_short'),
                3 => __('ui.weekdays.wednesday_short'),
                4 => __('ui.weekdays.thursday_short'),
                5 => __('ui.weekdays.friday_short'),
                6 => __('ui.weekdays.saturday_short'),
                7 => __('ui.weekdays.sunday_short'),
            ],
        ];
    }

    private function recurrenceStopMessage(?string $reason, Task $task): ?string
    {
        return match ($reason) {
            'missing_weekdays' => __('ui.feedback.task_recurrence_missing_weekdays'),
            'end_date' => __('ui.feedback.task_recurrence_end_date', [
                'date' => $task->recurrence_until?->translatedFormat('j M Y') ?? '',
            ]),
            'invalid_schedule' => __('ui.feedback.task_recurrence_unavailable'),
            default => null,
        };
    }

    private function availableCategories(User $user)
    {
        return Category::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('name')
            ->get();
    }

    private function deleteTaskOnly(Task $task, User $user, array $beingDeleted = []): void
    {
        if ($task->recurrence_parent_id === null) {
            $nextRoot = $user->tasks()
                ->where('recurrence_parent_id', $task->id)
                ->whereNotIn('id', $beingDeleted)
                ->orderBy('occurrence_date')
                ->orderBy('id')
                ->first();

            if ($nextRoot) {
                $user->tasks()
                    ->where('recurrence_parent_id', $task->id)
                    ->where('id', '!=', $nextRoot->id)
                    ->update(['recurrence_parent_id' => $nextRoot->id]);
                $nextRoot->update(['recurrence_parent_id' => null]);
            }
        }

        $user->tasks()->whereKey($task->id)->delete();
    }

    private function normalizeRecurrence(array $validated, User $user, ?Task $task = null): array
    {
        $recurrence = $validated['recurrence'] ?? $task?->recurrence ?? 'none';
        $validated['recurrence'] = $recurrence;

        if ($recurrence === 'weekly') {
            $validated['recurrence_days'] = $validated['recurrence_days'] ?? $task?->recurrence_days ?? [];
        } else {
            $validated['recurrence_days'] = null;
        }

        $validated['recurrence_until'] = $recurrence === 'none'
            ? null
            : (array_key_exists('recurrence_until', $validated)
                ? $validated['recurrence_until']
                : $task?->recurrence_until?->toDateString());
        $validated['recurrence_anchor_day'] = $recurrence === 'monthly'
            ? $this->monthlyAnchorDay($validated, $task, $user)
            : null;

        return $validated;
    }

    private function monthlyAnchorDay(array $validated, ?Task $task, User $user): int
    {
        foreach (['starts_at', 'due_date'] as $field) {
            if (! empty($validated[$field])) {
                return Carbon::parse($validated[$field], $user->timezone)->day;
            }
        }

        if ($task?->recurrence_anchor_day) {
            return $task->recurrence_anchor_day;
        }

        foreach ([$task?->starts_at, $task?->due_date] as $dateTime) {
            if ($dateTime) {
                return Carbon::instance($dateTime)->timezone($user->timezone)->day;
            }
        }

        return Carbon::now($user->timezone)->day;
    }

    private function isCompletedStatus(?int $statusId): bool
    {
        return $statusId !== null
            && TaskStatus::query()->whereKey($statusId)->where('is_completed', true)->exists();
    }

    private function resolveCategory(array $validated, Request $request): ?Category
    {
        if (! empty($validated['category_id'])) {
            $category = Category::query()->findOrFail($validated['category_id']);
            $this->authorize('useForTask', $category);

            return $category;
        }

        return null;
    }

    private function normalizeScheduleTimes(array $validated, User $user): array
    {
        foreach (['due_date', 'starts_at', 'ends_at'] as $field) {
            if (! empty($validated[$field])) {
                $validated[$field] = Carbon::parse($validated[$field], $user->timezone)
                    ->utc()
                    ->format('Y-m-d H:i:s');
            }
        }

        return $validated;
    }

    private function scheduleConflicts(Task $task): array
    {
        if (! $task->starts_at || ! $task->ends_at) {
            return [];
        }

        return $task->user->tasks()
            ->where('id', '!=', $task->id)
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<', $task->ends_at)
            ->where('ends_at', '>', $task->starts_at)
            ->orderBy('starts_at')
            ->limit(5)
            ->get(['title', 'starts_at', 'ends_at'])
            ->map(fn (Task $conflict) => [
                'title' => $conflict->title,
                'starts_at' => $conflict->starts_at->timezone($task->user->timezone)->format('d/m/Y H:i'),
                'ends_at' => $conflict->ends_at->timezone($task->user->timezone)->format('H:i'),
            ])
            ->all();
    }
}
