<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('goals', 'direction')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->string('direction', 10)->default('increase');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE goal_updates MODIFY amount BIGINT NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::table('goal_updates')->where('amount', '<', 0)->exists()) {
            throw new \RuntimeException('Cannot reverse goal direction migration while signed updates exist.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE goal_updates MODIFY amount BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('goals', 'direction')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->dropColumn('direction');
            });
        }
    }
};
