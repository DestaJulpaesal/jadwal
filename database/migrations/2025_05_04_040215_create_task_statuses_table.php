<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // contoh: Pending, Completed, etc.
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('task_status_id')->nullable()->constrained('task_statuses')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_status_id']);
            $table->dropColumn('task_status_id');
        });

        Schema::dropIfExists('task_statuses');
    }
};
