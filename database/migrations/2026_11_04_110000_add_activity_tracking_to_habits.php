<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->json('schedule_times')->nullable()->after('preferred_time');
        });

        Schema::table('habit_logs', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->nullable()->after('value');
            $table->string('performed_at', 5)->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('habit_logs', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'performed_at']);
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('schedule_times');
        });
    }
};
