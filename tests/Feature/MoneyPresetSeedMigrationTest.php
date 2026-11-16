<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoneyPresetSeedMigrationTest extends TestCase
{
    public function test_backfill_seeds_existing_users_once_and_marks_them_seeded(): void
    {
        $originalDefault = DB::getDefaultConnection();
        $connectionName = 'money_preset_migration_test';
        config([
            "database.connections.{$connectionName}" => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge($connectionName);
        DB::setDefaultConnection($connectionName);

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
            });
            Schema::create('money_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('type', 20);
                $table->string('name', 60);
                $table->timestamps();
                $table->unique(['user_id', 'type', 'name']);
            });
            DB::table('users')->insert([['id' => 1], ['id' => 2]]);
            DB::table('money_categories')->insert([
                'user_id' => 1,
                'type' => 'expense',
                'name' => 'Nama yang sudah dikustom',
            ]);
            $usersBefore = DB::table('users')->count();
            $categoriesBefore = DB::table('money_categories')->count();

            $migration = require database_path('migrations/2026_11_16_010000_add_money_presets_seeded_at_to_users.php');
            $migration->up();
            $migration->up();

            $presetCount = array_sum(array_map('count', config('presets.money_categories')));
            $this->assertSame($usersBefore, DB::table('users')->count());
            $this->assertSame($categoriesBefore + ($presetCount * 2), DB::table('money_categories')->count());
            $this->assertSame(2, DB::table('users')->whereNotNull('money_presets_seeded_at')->count());
            $this->assertSame(
                1,
                DB::table('money_categories')
                    ->where('user_id', 1)
                    ->where('name', 'Nama yang sudah dikustom')
                    ->count()
            );
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            $this->assertSame('ok', DB::selectOne('PRAGMA integrity_check')->integrity_check);
        } finally {
            DB::setDefaultConnection($originalDefault);
            DB::purge($connectionName);
        }
    }
}
