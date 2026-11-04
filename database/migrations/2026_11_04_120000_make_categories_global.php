<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::create('categories_global', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('name');
                $table->string('color')->nullable();
                $table->timestamps();
            });
            DB::statement('INSERT INTO categories_global (id, name, color, created_at, updated_at) SELECT id, name, color, created_at, updated_at FROM categories');
            Schema::drop('categories');
            Schema::rename('categories_global', 'categories');
            Schema::enableForeignKeyConstraints();
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        DB::table('categories')->update(['user_id' => null]);

        foreach ([
            ['name' => 'Kesehatan', 'color' => '#66D99B'],
            ['name' => 'Belajar', 'color' => '#82A5F4'],
            ['name' => 'Produktivitas', 'color' => '#FFD23F'],
            ['name' => 'Kehidupan', 'color' => '#FF9EC4'],
        ] as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name']],
                ['user_id' => null, 'color' => $category['color'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
