<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('money_categories', function (Blueprint $table): void {
            $table->string('name_normalized', 60)->default('');
        });

        DB::table('money_categories')
            ->orderBy('id')
            ->chunkById(200, function ($categories): void {
                foreach ($categories as $category) {
                    DB::table('money_categories')
                        ->where('id', $category->id)
                        ->update(['name_normalized' => mb_strtolower($category->name, 'UTF-8')]);
                }
            });

        $duplicateGroups = DB::table('money_categories')
            ->select('user_id', 'type', 'name_normalized')
            ->groupBy('user_id', 'type', 'name_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $categories = DB::table('money_categories')
                ->where('user_id', $group->user_id)
                ->where('type', $group->type)
                ->where('name_normalized', $group->name_normalized)
                ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get();
            $keepId = $categories->first()->id;

            foreach ($categories->skip(1) as $duplicate) {
                DB::table('transactions')
                    ->where('money_category_id', $duplicate->id)
                    ->update(['money_category_id' => $keepId]);
                DB::table('obligations')
                    ->where('money_category_id', $duplicate->id)
                    ->update(['money_category_id' => $keepId]);

                $duplicateBudgets = DB::table('budgets')
                    ->where('money_category_id', $duplicate->id)
                    ->orderBy('id')
                    ->get();

                foreach ($duplicateBudgets as $budget) {
                    $existingBudget = DB::table('budgets')
                        ->where('user_id', $budget->user_id)
                        ->where('money_category_id', $keepId)
                        ->where('month', $budget->month)
                        ->first();

                    if ($existingBudget) {
                        DB::table('budgets')
                            ->where('id', $existingBudget->id)
                            ->update([
                                'amount' => $existingBudget->amount + $budget->amount,
                                'updated_at' => now(),
                            ]);
                        DB::table('budgets')->where('id', $budget->id)->delete();

                        continue;
                    }

                    DB::table('budgets')
                        ->where('id', $budget->id)
                        ->update(['money_category_id' => $keepId]);
                }

                DB::table('money_categories')->where('id', $duplicate->id)->delete();
            }
        }

        DB::statement(
            'CREATE UNIQUE INDEX money_categories_user_type_name_normalized_unique '
            .'ON money_categories (user_id, type, name_normalized)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS money_categories_user_type_name_normalized_unique');

        Schema::table('money_categories', function (Blueprint $table): void {
            $table->dropColumn('name_normalized');
        });
    }
};
