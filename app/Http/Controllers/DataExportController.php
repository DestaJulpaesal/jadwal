<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportDataRequest;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function export(ExportDataRequest $request, string $type): StreamedResponse
    {
        $user = $request->user();
        $filters = $request->validated();

        [$headers, $rows, $filename] = match ($type) {
            'transactions' => $this->transactions($user, $filters),
            'tasks' => $this->tasks($user),
            'habits' => $this->habits($user),
            'habit-logs' => $this->habitLogs($user),
            default => abort(404),
        };

        Log::info('export', [
            'user_id' => $user->id,
            'type' => $type,
        ]);

        return response()->streamDownload(
            fn () => $this->writeCsv($headers, $rows),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * @return array{array<int, string>, LazyCollection, string}
     */
    private function transactions(User $user, array $filters): array
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $rows = Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->with([
                'wallet' => fn ($query) => $query->where('user_id', $user->id),
                'destinationWallet' => fn ($query) => $query->where('user_id', $user->id),
                'moneyCategory' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->lazyById(500)
            ->map(fn (Transaction $transaction) => [
                $transaction->id,
                $transaction->occurred_on->toDateString(),
                $transaction->type->value,
                $transaction->amount,
                $this->safeText($transaction->wallet?->name),
                $this->safeText($transaction->destinationWallet?->name),
                $this->safeText($transaction->moneyCategory?->name),
                $this->safeText($transaction->note),
                $this->safeText($transaction->source_type),
                $transaction->source_id,
            ]);

        return [__('ui.export.columns.transactions'), $rows, "transaksi-{$startDate}-{$endDate}.csv"];
    }

    /**
     * @return array{array<int, string>, LazyCollection, string}
     */
    private function tasks(User $user): array
    {
        $rows = Task::query()
            ->where('user_id', $user->id)
            ->with([
                'status',
                'category' => fn ($query) => $query->where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id)->orWhereNull('user_id');
                }),
            ])
            ->lazyById(500)
            ->map(fn (Task $task) => [
                $task->id,
                $this->safeText($task->title),
                $this->safeText($task->description),
                $this->safeText($task->status?->name),
                $this->safeText($task->category?->name),
                $this->localDateTime($task->starts_at, $user),
                $this->localDateTime($task->ends_at, $user),
                $this->localDateTime($task->due_date, $user),
                $task->completed_at?->timezone($user->timezone)->format('Y-m-d H:i:s') ?? '',
                $this->safeText($task->recurrence),
                $this->safeText(implode(',', array_map('intval', $task->recurrence_days ?? []))),
                $task->recurrence_anchor_day,
                $task->recurrence_until?->toDateString() ?? '',
                $task->occurrence_date?->toDateString() ?? '',
            ]);

        return [__('ui.export.columns.tasks'), $rows, 'tugas.csv'];
    }

    /**
     * @return array{array<int, string>, LazyCollection, string}
     */
    private function habits(User $user): array
    {
        $rows = Habit::query()
            ->where('user_id', $user->id)
            ->with([
                'category' => fn ($query) => $query->where(function ($query) use ($user): void {
                    $query->where('user_id', $user->id)->orWhereNull('user_id');
                }),
            ])
            ->lazyById(500)
            ->map(fn (Habit $habit) => [
                $habit->id,
                $this->safeText($habit->title),
                $this->safeText($habit->description),
                $this->safeText($habit->frequency->value),
                $this->safeText(implode(',', $habit->weekdays ?? [])),
                $habit->weekly_target,
                $habit->goal_value,
                $this->safeText($habit->goal_unit),
                $this->safeText($habit->preferred_time),
                $habit->starts_on->toDateString(),
                $habit->ends_on?->toDateString() ?? '',
                $this->safeText($habit->category?->name),
                $habit->archived_at?->timezone($user->timezone)->format('Y-m-d H:i:s') ?? '',
            ]);

        return [__('ui.export.columns.habits'), $rows, 'kebiasaan.csv'];
    }

    /**
     * @return array{array<int, string>, LazyCollection, string}
     */
    private function habitLogs(User $user): array
    {
        $rows = HabitLog::query()
            ->where('user_id', $user->id)
            ->whereHas('habit', fn ($query) => $query->where('user_id', $user->id))
            ->with('habit')
            ->lazyById(500)
            ->map(fn (HabitLog $log) => [
                $log->id,
                $log->habit_id,
                $this->safeText($log->habit?->title),
                $log->log_date->toDateString(),
                $this->safeText($log->status->label()),
                $log->value,
                $log->duration_minutes,
                $this->safeText($log->performed_at),
                $this->safeText($log->note),
            ]);

        return [__('ui.export.columns.habit_logs'), $rows, 'log-kebiasaan.csv'];
    }

    private function writeCsv(array $headers, LazyCollection $rows): void
    {
        $stream = fopen('php://output', 'wb');
        if ($stream === false) {
            throw new RuntimeException('CSV export stream could not be opened.');
        }

        try {
            if (fwrite($stream, "\xEF\xBB\xBF") !== 3) {
                throw new RuntimeException('CSV export BOM could not be written.');
            }

            $this->writeRow($stream, $headers);
            foreach ($rows as $row) {
                $this->writeRow($stream, $row);
            }
        } finally {
            fclose($stream);
        }
    }

    private function writeRow($stream, array $row): void
    {
        if (fputcsv($stream, $row, ',', '"', '\\', "\r\n") === false) {
            throw new RuntimeException('A CSV export row could not be written.');
        }
    }

    private function safeText(?string $value): string
    {
        if ($value !== null && preg_match('/\A[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value ?? '';
    }

    private function localDateTime(?\DateTimeInterface $value, User $user): string
    {
        return $value === null
            ? ''
            : \Illuminate\Support\Carbon::instance($value)->timezone($user->timezone)->format('Y-m-d H:i:s');
    }
}
