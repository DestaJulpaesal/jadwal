<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoneyCategoryCaseInsensitiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_merges_case_variants_and_preserves_linked_data(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => 'cash']);
        DB::statement('DROP INDEX money_categories_user_type_name_normalized_unique');
        Schema::table('money_categories', function (Blueprint $table): void {
            $table->dropColumn('name_normalized');
        });

        $archivedCategoryId = DB::table('money_categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'archived_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $activeCategoryId = DB::table('money_categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'makan',
            'type' => 'expense',
            'archived_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $firstTransaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $archivedCategoryId,
            'type' => 'expense',
            'amount' => 10_000,
            'occurred_on' => '2026-11-01',
        ]);
        $secondTransaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $activeCategoryId,
            'type' => 'expense',
            'amount' => 20_000,
            'occurred_on' => '2026-11-02',
        ]);
        $obligation = $user->obligations()->create([
            'kind' => 'bill',
            'title' => 'Belanja',
            'amount' => 10_000,
            'wallet_id' => $wallet->id,
            'money_category_id' => $archivedCategoryId,
            'due_day' => 15,
            'starts_on' => '2026-11-01',
        ]);
        $user->budgets()->create([
            'money_category_id' => $archivedCategoryId,
            'month' => '2026-11',
            'amount' => 1_000_000,
        ]);
        $user->budgets()->create([
            'money_category_id' => $activeCategoryId,
            'month' => '2026-11',
            'amount' => 2_000_000,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $firstTransaction->id,
            'money_category_id' => $archivedCategoryId,
        ]);

        $migration = require database_path('migrations/2026_11_20_000000_make_money_category_names_case_insensitive.php');
        $migration->up();

        $this->assertSame(
            [$activeCategoryId],
            DB::table('money_categories')->where('user_id', $user->id)->pluck('id')->all()
        );
        $this->assertDatabaseHas('money_categories', [
            'id' => $activeCategoryId,
            'name_normalized' => 'makan',
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $firstTransaction->id,
            'money_category_id' => $activeCategoryId,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $secondTransaction->id,
            'money_category_id' => $activeCategoryId,
        ]);
        $this->assertDatabaseHas('obligations', [
            'id' => $obligation->id,
            'money_category_id' => $activeCategoryId,
        ]);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'money_category_id' => $activeCategoryId,
            'month' => '2026-11',
            'amount' => 3_000_000,
        ]);
        $this->assertDatabaseMissing('money_categories', ['id' => $archivedCategoryId]);
    }
}
