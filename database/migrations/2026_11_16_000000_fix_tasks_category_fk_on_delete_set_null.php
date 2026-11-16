<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });

            return;
        }

        if ($this->categoryForeignKeyOnDelete() === 'SET NULL') {
            return;
        }

        $this->rebuildTasks('SET NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });

            return;
        }

        if ($this->categoryForeignKeyOnDelete() === 'CASCADE') {
            return;
        }

        // Rolling back restores the legacy behavior; a later category delete can cascade to tasks.
        $this->rebuildTasks('CASCADE');
    }

    private function categoryForeignKeyOnDelete(): string
    {
        foreach (DB::select("PRAGMA foreign_key_list('tasks')") as $foreignKey) {
            if ($foreignKey->table === 'categories' && $foreignKey->from === 'category_id') {
                return strtoupper($foreignKey->on_delete);
            }
        }

        throw new RuntimeException('The tasks.category_id foreign key was not found.');
    }

    private function rebuildTasks(string $categoryOnDelete): void
    {
        if (DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('The tasks foreign key migration must run outside a transaction.');
        }

        $sequence = DB::table('sqlite_sequence')->where('name', 'tasks')->value('seq');
        $transactionStarted = false;

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::statement('BEGIN IMMEDIATE');
            $transactionStarted = true;

            DB::statement("
                CREATE TABLE tasks_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INTEGER NOT NULL,
                    category_id INTEGER NULL,
                    title VARCHAR NOT NULL,
                    description TEXT NULL,
                    due_date DATETIME NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    task_status_id INTEGER NULL,
                    starts_at DATETIME NULL,
                    ends_at DATETIME NULL,
                    completed_at DATETIME NULL,
                    CONSTRAINT tasks_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    CONSTRAINT tasks_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE {$categoryOnDelete},
                    CONSTRAINT tasks_task_status_id_foreign FOREIGN KEY (task_status_id) REFERENCES task_statuses (id) ON DELETE SET NULL
                )
            ");
            DB::statement('
                INSERT INTO tasks_new (
                    id, user_id, category_id, title, description, due_date,
                    created_at, updated_at, task_status_id, starts_at, ends_at, completed_at
                )
                SELECT
                    id, user_id, category_id, title, description, due_date,
                    created_at, updated_at, task_status_id, starts_at, ends_at, completed_at
                FROM tasks
            ');
            DB::statement('DROP TABLE tasks');
            DB::statement('ALTER TABLE tasks_new RENAME TO tasks');

            if ($sequence !== null) {
                DB::table('sqlite_sequence')
                    ->where('name', 'tasks')
                    ->update(['seq' => max((int) $sequence, (int) DB::table('sqlite_sequence')->where('name', 'tasks')->value('seq'))]);
            }

            DB::statement('CREATE INDEX tasks_user_status_index ON tasks (user_id, task_status_id)');
            DB::statement('CREATE INDEX tasks_user_due_date_index ON tasks (user_id, due_date)');
            DB::statement('CREATE INDEX tasks_user_starts_at_index ON tasks (user_id, starts_at)');
            DB::statement('CREATE INDEX tasks_user_id_completed_at_index ON tasks (user_id, completed_at)');

            $violations = DB::select('PRAGMA foreign_key_check');
            if ($violations !== []) {
                throw new RuntimeException('The tasks foreign key migration produced foreign key violations.');
            }

            DB::statement('COMMIT');
            $transactionStarted = false;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                DB::statement('ROLLBACK');
            }

            throw $exception;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
