<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $todayStartLocal = Carbon::now($user->timezone)->startOfDay();
        $tomorrowStartLocal = $todayStartLocal->copy()->addDay();
        $nextWeekStartLocal = $tomorrowStartLocal->copy()->addDays(7);
        $totalTasks = $user->tasks()->count();
        $todayTasks = $this->tasksInRange(
            $user,
            $todayStartLocal->copy()->utc(),
            $tomorrowStartLocal->copy()->utc()
        );
        $upcomingTasks = $this->tasksInRange(
            $user,
            $tomorrowStartLocal->copy()->utc(),
            $nextWeekStartLocal->copy()->utc(),
            true
        );
        $nowUtc = Carbon::now('UTC');
        $overdueTasks = $user->tasks()
            ->with(['category', 'status'])
            ->whereDoesntHave('status', fn ($query) => $query->where('name', 'Selesai'))
            ->where(function ($query) use ($nowUtc) {
                $query->where(function ($query) use ($nowUtc) {
                    $query->whereNotNull('due_date')->where('due_date', '<', $nowUtc);
                })->orWhere(function ($query) use ($nowUtc) {
                    $query->whereNull('due_date')
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<', $nowUtc);
                });
            })
            ->get()
            ->sortBy(fn ($task) => ($task->due_date ?? $task->ends_at)->timestamp);

        return view('user.dashboard', compact('todayTasks', 'upcomingTasks', 'overdueTasks', 'totalTasks'));
    }

    private function tasksInRange(User $user, Carbon $startUtc, Carbon $endUtc, bool $excludeCompleted = false): Collection
    {
        $query = $user->tasks()
            ->with(['category', 'status'])
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->where('starts_at', '>=', $startUtc)
                    ->where('starts_at', '<', $endUtc)
                    ->orWhere(function ($query) use ($startUtc, $endUtc) {
                        $query->whereNull('starts_at')
                            ->where('due_date', '>=', $startUtc)
                            ->where('due_date', '<', $endUtc);
                    });
            });

        if ($excludeCompleted) {
            $query->whereDoesntHave('status', fn ($query) => $query->where('name', 'Selesai'));
        }

        return $query->get()
            ->sortBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timestamp);
    }

    public function calendar(Request $request)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $user = $request->user();
        $timezone = $user->timezone;
        $now = Carbon::now($timezone);
        $month = Carbon::createFromDate(
            $validated['year'] ?? $now->year,
            $validated['month'] ?? $now->month,
            1,
            $timezone
        )->startOfMonth();
        $calendarStart = $month->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $rangeStartUtc = $calendarStart->copy()->utc();
        $rangeEndUtc = $calendarEnd->copy()->utc();
        $tasksByDay = $user->tasks()
            ->with(['category', 'status'])
            ->where(function ($query) use ($rangeStartUtc, $rangeEndUtc) {
                $query->whereBetween('starts_at', [$rangeStartUtc, $rangeEndUtc])
                    ->orWhere(function ($query) use ($rangeStartUtc, $rangeEndUtc) {
                        $query->whereNull('starts_at')
                            ->whereBetween('due_date', [$rangeStartUtc, $rangeEndUtc]);
                    });
            })
            ->get()
            ->sortBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timestamp)
            ->groupBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timezone($timezone)->toDateString());
        $days = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $day = $date->copy();
            $days->push([
                'date' => $day,
                'in_month' => $day->month === $month->month,
                'tasks' => $tasksByDay->get($day->toDateString(), collect()),
            ]);
        }

        $weeks = $days->chunk(7);
        $previousMonth = $month->copy()->subMonthNoOverflow();
        $nextMonth = $month->copy()->addMonthNoOverflow();

        return view('user.calendar', compact('month', 'weeks', 'previousMonth', 'nextMonth'));
    }
}
