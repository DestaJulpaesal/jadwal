<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('week_starts_on', 10)->default('monday');
            $table->string('theme', 10)->default('light');
            $table->string('digest_time', 5)->default('07:00');
            $table->json('digest_days')->nullable();
            $table->boolean('hide_financial_amounts')->default(false);
            $table->json('dashboard_sections')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'week_starts_on',
                'theme',
                'digest_time',
                'digest_days',
                'hide_financial_amounts',
                'dashboard_sections',
            ]);
        });
    }
};
