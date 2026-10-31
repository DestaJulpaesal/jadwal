<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'timezone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('timezone')->default('Asia/Jakarta');
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
        });

        // Nilai lama tidak menyimpan zona waktu dan sebelumnya diisi sebagai waktu Jakarta.
        $this->convertDueDates('Asia/Jakarta', 'UTC');
    }

    public function down(): void
    {
        $this->convertDueDates('UTC', 'Asia/Jakarta');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });

        // SQLite dapat menghapus tugas berelasi saat tabel users dibangun ulang.
    }

    private function convertDueDates(string $fromTimezone, string $toTimezone): void
    {
        DB::table('tasks')
            ->whereNotNull('due_date')
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use ($fromTimezone, $toTimezone) {
                foreach ($tasks as $task) {
                    $dueDate = Carbon::parse($task->due_date, $fromTimezone)
                        ->setTimezone($toTimezone)
                        ->format('Y-m-d H:i:s');

                    DB::table('tasks')
                        ->where('id', $task->id)
                        ->update(['due_date' => $dueDate]);
                }
            });
    }
};
