<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskCategoryForeignKeyMigrationTest extends TestCase
{
    public function test_sqlite_rebuild_preserves_tasks_indexes_and_other_foreign_key_actions(): void
    {
        $originalDefault = DB::getDefaultConnection();
        $connectionName = 'task_category_migration_test';

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
            $connection = DB::connection();
            $connection->statement('PRAGMA foreign_keys = ON');

            Schema::create('users', function (Blueprint $table) {
                $table->id();
            });
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
            });
            Schema::create('task_statuses', function (Blueprint $table) {
                $table->id();
            });
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('due_date')->nullable();
                $table->timestamps();
                $table->foreignId('task_status_id')->nullable()->constrained()->nullOnDelete();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->index(['user_id', 'task_status_id'], 'tasks_user_status_index');
                $table->index(['user_id', 'due_date'], 'tasks_user_due_date_index');
                $table->index(['user_id', 'starts_at'], 'tasks_user_starts_at_index');
                $table->index(['user_id', 'completed_at']);
            });

            DB::table('users')->insert([['id' => 1], ['id' => 2]]);
            DB::table('categories')->insert(['id' => 1]);
            DB::table('task_statuses')->insert(['id' => 1]);
            DB::table('tasks')->insert([
                ['id' => 1, 'user_id' => 1, 'category_id' => 1, 'title' => 'First', 'task_status_id' => 1],
                ['id' => 2, 'user_id' => 2, 'category_id' => 1, 'title' => 'Second', 'task_status_id' => 1],
            ]);
            DB::table('sqlite_sequence')->where('name', 'tasks')->update(['seq' => 100]);

            $migration = require database_path('migrations/2026_11_16_000000_fix_tasks_category_fk_on_delete_set_null.php');
            $migration->up();
            $migration->up();

            $this->assertSame(2, DB::table('tasks')->count());
            $this->assertSame(100, (int) DB::table('sqlite_sequence')->where('name', 'tasks')->value('seq'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

            $indexes = array_column(DB::select("PRAGMA index_list('tasks')"), 'name');
            $this->assertEqualsCanonicalizing([
                'tasks_user_status_index',
                'tasks_user_due_date_index',
                'tasks_user_starts_at_index',
                'tasks_user_id_completed_at_index',
            ], $indexes);

            $foreignKeys = DB::select("PRAGMA foreign_key_list('tasks')");
            $categoryForeignKey = collect($foreignKeys)->firstWhere('from', 'category_id');
            $userForeignKey = collect($foreignKeys)->firstWhere('from', 'user_id');
            $statusForeignKey = collect($foreignKeys)->firstWhere('from', 'task_status_id');
            $this->assertSame('SET NULL', strtoupper($categoryForeignKey->on_delete));
            $this->assertSame('CASCADE', strtoupper($userForeignKey->on_delete));
            $this->assertSame('SET NULL', strtoupper($statusForeignKey->on_delete));

            DB::table('categories')->where('id', 1)->delete();
            $this->assertSame(2, DB::table('tasks')->count());
            $this->assertSame(2, DB::table('tasks')->whereNull('category_id')->count());
        } finally {
            DB::setDefaultConnection($originalDefault);
            DB::purge($connectionName);
        }
    }
}
