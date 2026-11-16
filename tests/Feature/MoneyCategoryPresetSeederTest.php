<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MoneyCategoryPresetSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_getting_the_money_page_does_not_restore_renamed_presets_or_write_categories(): void
    {
        $user = User::factory()->create();
        $category = $user->moneyCategories()->create([
            'name' => 'Makan & minum',
            'type' => 'expense',
        ]);
        $category->update(['name' => 'Makan kustom']);
        $writes = [];

        DB::listen(function (QueryExecuted $query) use (&$writes) {
            $sql = strtolower(ltrim($query->sql));

            if (preg_match('/^(insert|update|delete)\b/', $sql) && str_contains($sql, 'money_categories')) {
                $writes[] = $query->sql;
            }
        });

        $this->actingAs($user)->get(route('user.money.index'))->assertOk();
        $this->get(route('user.money.index'))->assertOk();

        $this->assertDatabaseHas('money_categories', [
            'user_id' => $user->id,
            'name' => 'Makan kustom',
            'type' => 'expense',
        ]);
        $this->assertDatabaseMissing('money_categories', [
            'user_id' => $user->id,
            'name' => 'Makan & minum',
            'type' => 'expense',
        ]);
        $this->assertSame([], $writes);
    }
}
