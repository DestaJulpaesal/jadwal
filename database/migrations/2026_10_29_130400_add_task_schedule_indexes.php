<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'task_status_id'], 'tasks_user_status_index');
            $table->index(['user_id', 'due_date'], 'tasks_user_due_date_index');
            $table->index(['user_id', 'starts_at'], 'tasks_user_starts_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_status_index');
            $table->dropIndex('tasks_user_due_date_index');
            $table->dropIndex('tasks_user_starts_at_index');
        });
    }
};
