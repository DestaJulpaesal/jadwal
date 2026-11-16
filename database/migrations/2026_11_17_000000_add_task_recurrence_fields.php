<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('recurrence', 16)->default('none');
            $table->json('recurrence_days')->nullable();
            $table->date('recurrence_until')->nullable();
            $table->unsignedTinyInteger('recurrence_anchor_day')->nullable();
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('occurrence_date')->nullable();
            $table->unique(['recurrence_parent_id', 'occurrence_date'], 'tasks_recurrence_occurrence_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_recurrence_occurrence_unique');
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn([
                'recurrence',
                'recurrence_days',
                'recurrence_until',
                'recurrence_anchor_day',
                'recurrence_parent_id',
                'occurrence_date',
            ]);
        });
    }
};
