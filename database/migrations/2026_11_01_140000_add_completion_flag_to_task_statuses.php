<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false);
        });

        DB::table('task_statuses')->where('name', 'Selesai')->update(['is_completed' => true]);
    }

    public function down(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }
};
