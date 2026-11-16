<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class TaskRecurrence
{
    public function createNext(Task $task, User $user): ?Task
    {
        if ($task->recurrence === 'none') {
            return null;
        }

        $timezone = $user->timezone;
        $currentDate = $this->scheduleDate($task, $timezone);
        $seriesId = $task->recurrence_parent_id ?? $task->id;
        $seriesRoot = $user->tasks()->find($seriesId);

        if (! $seriesRoot) {
            $seriesId = $task->id;
            $seriesRoot = $task;
        }

        $anchorDay = max(1, min(31, $seriesRoot->recurrence_anchor_day ?? $this->scheduleDate($seriesRoot, $timezone)->day));
        $days = array_map('intval', $task->recurrence_days ?? []);
        $nextDate = $this->nextDate($currentDate, $task->recurrence, $days, $anchorDay);

        if ($nextDate === null) {
            return null;
        }

        $today = CarbonImmutable::now($timezone)->startOfDay();
        if ($task->recurrence === 'daily' && $nextDate->lt($today)) {
            $nextDate = $nextDate->addDays($nextDate->diffInDays($today));
        } else {
            while ($nextDate->lt($today)) {
                $nextDate = $this->nextDate($nextDate, $task->recurrence, $days, $anchorDay);
                if ($nextDate === null) {
                    return null;
                }
            }
        }

        if ($task->recurrence_until && $nextDate->gt($task->recurrence_until->startOfDay())) {
            return null;
        }

        $localStart = $this->localDateTime($task->starts_at, $timezone);
        $localEnd = $this->localDateTime($task->ends_at, $timezone);
        $localDue = $this->localDateTime($task->due_date, $timezone);
        $startAt = $localStart
            ? $this->onDate($nextDate, $localStart)->utc()
            : null;
        $endAt = $startAt && $localEnd
            ? $startAt->addSeconds(CarbonImmutable::instance($task->ends_at)->diffInSeconds($task->starts_at))
            : null;
        $dueDate = $localDue
            ? $this->onDate(
                $nextDate->addDays($currentDate->diffInDays($localDue->startOfDay(), false)),
                $localDue
            )->utc()
            : null;

        return $user->tasks()->firstOrCreate(
            [
                'recurrence_parent_id' => $seriesId,
                'occurrence_date' => $nextDate->toDateString(),
            ],
            [
                'category_id' => $task->category_id,
                'title' => $task->title,
                'description' => $task->description,
                'starts_at' => $startAt,
                'ends_at' => $endAt,
                'due_date' => $dueDate,
                'recurrence' => $task->recurrence,
                'recurrence_days' => $task->recurrence_days,
                'recurrence_until' => $task->recurrence_until?->toDateString(),
                'recurrence_anchor_day' => $task->recurrence_anchor_day,
            ]
        );
    }

    private function scheduleDate(Task $task, string $timezone): CarbonImmutable
    {
        if ($task->occurrence_date) {
            return CarbonImmutable::parse($task->occurrence_date->toDateString(), $timezone)->startOfDay();
        }

        foreach ([$task->starts_at, $task->due_date] as $dateTime) {
            if ($dateTime) {
                return CarbonImmutable::instance($dateTime)->setTimezone($timezone)->startOfDay();
            }
        }

        return CarbonImmutable::now($timezone)->startOfDay();
    }

    private function nextDate(CarbonImmutable $date, string $frequency, array $days, int $anchorDay): ?CarbonImmutable
    {
        return match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $this->nextWeekday($date, $days),
            'monthly' => $this->nextMonth($date, $anchorDay),
            default => null,
        };
    }

    private function nextWeekday(CarbonImmutable $date, array $days): ?CarbonImmutable
    {
        for ($offset = 1; $offset <= 7; $offset++) {
            $candidate = $date->addDays($offset);
            if (in_array($candidate->dayOfWeekIso, $days, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function nextMonth(CarbonImmutable $date, int $anchorDay): CarbonImmutable
    {
        $month = $date->startOfMonth()->addMonthNoOverflow();

        return $month->setDay(min($anchorDay, $month->daysInMonth));
    }

    private function localDateTime(?DateTimeInterface $value, string $timezone): ?CarbonImmutable
    {
        return $value === null
            ? null
            : CarbonImmutable::instance($value)->setTimezone($timezone);
    }

    private function onDate(CarbonImmutable $date, CarbonImmutable $time): CarbonImmutable
    {
        return $date->setTime($time->hour, $time->minute, $time->second);
    }
}
