<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('light')->change();
        });

        DB::table('users')->where('theme', 'system')->update(['theme' => 'light']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('system')->change();
        });
    }
};
