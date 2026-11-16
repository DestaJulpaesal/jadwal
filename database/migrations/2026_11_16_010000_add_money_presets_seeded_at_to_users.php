<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'money_presets_seeded_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('money_presets_seeded_at')->nullable();
            });
        }

        $presets = config('presets.money_categories', []);
        DB::table('users')
            ->whereNull('money_presets_seeded_at')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($presets) {
                foreach ($users as $user) {
                    $timestamp = now()->toDateTimeString();
                    $categories = [];

                    foreach ($presets as $type => $names) {
                        foreach ($names as $name) {
                            $categories[] = [
                                'user_id' => $user->id,
                                'type' => $type,
                                'name' => $name,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        }
                    }

                    if ($categories !== []) {
                        DB::table('money_categories')->insertOrIgnore($categories);
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('money_presets_seeded_at')
                        ->update(['money_presets_seeded_at' => $timestamp]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'money_presets_seeded_at')) {
            // Seeded categories may have been used or renamed; retain them on rollback.
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('money_presets_seeded_at');
            });
        }
    }
};
