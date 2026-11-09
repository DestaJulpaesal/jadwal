<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Support\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status_id' => ['nullable', 'integer', 'exists:task_statuses,id'],
            'due' => ['nullable', Rule::in(['today', 'upcoming', 'overdue', 'none'])],
        ]);
        $query = auth()->user()->tasks()->with(['category', 'status']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('task_status_id', $filters['status_id']);
        }

        $todayStartLocal = Carbon::now($request->user()->timezone)->startOfDay();
        $todayStart = $todayStartLocal->copy()->utc();
        $tomorrowStart = $todayStartLocal->copy()->addDay()->utc();

        match ($filters['due'] ?? null) {
            'today' => $query->where('due_date', '>=', $todayStart)
                ->where('due_date', '<', $tomorrowStart),
            'upcoming' => $query->where('due_date', '>=', $tomorrowStart),
            'overdue' => $query
                ->whereDoesntHave('status', fn ($query) => $query->where('is_completed', true))
                ->where(function ($query) {
                    $now = Carbon::now('UTC');
                    $query->where(fn ($query) => $query->whereNotNull('due_date')->where('due_date', '<', $now))
                        ->orWhere(fn ($query) => $query->whereNull('due_date')->whereNotNull('ends_at')->where('ends_at', '<', $now));
                }),
            'none' => $query->whereNull('due_date'),
            default => null,
        };

        $tasks = $query->latest()->paginate(10)->withQueryString();
        $statuses = TaskStatus::orderBy('name')->get();

        return view('user.tasks.index', compact('tasks', 'statuses', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        return view('user.tasks.create', $this->formData());
    }

    public function store(SaveTaskRequest $request)
    {
        $validated = $this->normalizeScheduleTimes($request->validated(), $request->user());

        $task = DB::transaction(function () use ($validated, $request) {
            $category = $this->resolveCategory($validated, $request);

            return $request->user()->tasks()->create([
                'category_id' => $category?->id,
                'task_status_id' => $validated['task_status_id'] ?? null,
                'completed_at' => $this->isCompletedStatus($validated['task_status_id'] ?? null) ? now('UTC') : null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
            ]);
        });

        return Feedback::success(
            redirect()->route('user.tasks.index')->with('schedule_conflicts', $this->scheduleConflicts($task)),
            __('ui.feedback.task_created')
        );
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        return view('user.tasks.edit', array_merge($this->formData(), compact('task')));
    }

    public function update(SaveTaskRequest $request, Task $task)
    {
        $validated = $this->normalizeScheduleTimes($request->validated(), $request->user());
        $wasCompleted = $task->status()->where('is_completed', true)->exists();
        $isCompleted = $this->isCompletedStatus($validated['task_status_id'] ?? null);
        $completedAt = ! $wasCompleted && $isCompleted ? now('UTC') : $task->completed_at;

        DB::transaction(function () use ($validated, $request, $task, $completedAt) {
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
            ]);
        });

        return Feedback::success(
            redirect()->route('user.tasks.index')->with('schedule_conflicts', $this->scheduleConflicts($task->refresh())),
            __('ui.feedback.task_updated')
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

        if ($task->task_status_id === $completedStatus->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('ui.feedback.task_already_completed')]);
            }

            return Feedback::info(back(), __('ui.feedback.task_already_completed'));
        }

        $task->update([
            'task_status_id' => $completedStatus->id,
            'completed_at' => now('UTC'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('ui.feedback.task_completed'),
                'status' => $completedStatus->name,
            ]);
        }

        return Feedback::success(back(), __('ui.feedback.task_completed'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        return Feedback::success(redirect()->route('user.tasks.index'), __('ui.feedback.task_deleted'));
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'statuses' => TaskStatus::orderBy('name')->get(),
        ];
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
