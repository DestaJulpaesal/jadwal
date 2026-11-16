<?php

namespace Tests\Feature;

use App\Enums\HabitFrequency;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_export_streams_owned_rows_with_utf8_bom_and_safe_text(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $otherWallet = $otherUser->wallets()->create(['name' => 'Pribadi', 'type' => WalletType::Cash]);
        $unsafeNotes = ['=SUM(1,1)', '+cmd', '-cmd', '@cmd', "\tcmd", "\rcmd"];

        foreach ($unsafeNotes as $note) {
            $user->transactions()->create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Expense,
                'amount' => 1000,
                'occurred_on' => '2026-06-15',
                'note' => $note,
            ]);
        }

        $user->transactions()->create([
            'wallet_id' => $otherWallet->id,
            'type' => TransactionType::Expense,
            'amount' => 500,
            'occurred_on' => '2026-06-15',
            'note' => 'Referensi dompet asing',
        ]);

        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 2000,
            'occurred_on' => '2026-05-31',
            'note' => 'Di luar rentang',
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 3000,
            'occurred_on' => '2026-07-01',
            'note' => 'Di luar rentang',
        ]);
        $otherUser->transactions()->create([
            'wallet_id' => $otherWallet->id,
            'type' => TransactionType::Expense,
            'amount' => 4000,
            'occurred_on' => '2026-06-15',
            'note' => 'Jangan diekspor',
        ]);

        Log::spy();

        $response = $this->actingAs($user)->get(route('settings.exports', [
            'type' => 'transactions',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $content = $response->streamedContent();
        Log::shouldHaveReceived('info')
            ->once()
            ->with('export', ['user_id' => $user->id, 'type' => 'transactions']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $rows = $this->parseCsv(substr($content, 3));
        $this->assertSame(__('ui.export.columns.transactions'), $rows[0]);
        $this->assertCount(count($unsafeNotes) + 1, array_slice($rows, 1));
        $this->assertSame('1000', $rows[1][3]);

        foreach ($unsafeNotes as $index => $note) {
            $this->assertSame("'".$note, $rows[$index + 1][7]);
        }

        $this->assertSame('', $rows[count($unsafeNotes) + 1][4]);
        $this->assertStringNotContainsString('Jangan diekspor', $content);
        $this->assertStringNotContainsString('Di luar rentang', $content);
    }

    public function test_export_supports_the_exact_five_year_range_and_rejects_longer_or_reversed_ranges(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from(route('settings.edit'))->get(route('settings.exports', [
            'type' => 'transactions',
            'start_date' => '2021-01-01',
            'end_date' => '2026-01-01',
        ]))->assertOk();

        $this->from(route('settings.edit'))->get(route('settings.exports', [
            'type' => 'transactions',
            'start_date' => '2021-01-01',
            'end_date' => '2026-01-02',
        ]))->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('end_date');

        $this->from(route('settings.edit'))->get(route('settings.exports', [
            'type' => 'transactions',
            'start_date' => '2026-01-02',
            'end_date' => '2026-01-01',
        ]))->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_task_habit_and_habit_log_exports_only_include_the_current_users_data(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $otherUser = User::factory()->create();
        $user->tasks()->create(['title' => '=Judul tugas', 'starts_at' => '2026-06-15 01:00:00']);
        $otherUser->tasks()->create(['title' => 'Tugas rahasia']);

        $habit = $user->habits()->create([
            'title' => '@Kebiasaan',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-06-01',
        ]);
        $otherHabit = $otherUser->habits()->create([
            'title' => 'Kebiasaan rahasia',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-06-01',
        ]);
        $user->habitLogs()->create([
            'habit_id' => $otherHabit->id,
            'log_date' => '2026-06-16',
            'status' => 'done',
            'note' => 'Log dengan kebiasaan asing',
        ]);
        $user->habitLogs()->create([
            'habit_id' => $habit->id,
            'log_date' => '2026-06-15',
            'status' => 'done',
            'note' => '+Catatan',
        ]);
        $otherUser->habitLogs()->create([
            'habit_id' => $otherHabit->id,
            'log_date' => '2026-06-15',
            'status' => 'done',
            'note' => 'Catatan rahasia',
        ]);

        $this->actingAs($user);

        $tasks = $this->get(route('settings.exports', 'tasks'))->assertOk();
        $taskRows = $this->parseCsv(substr($tasks->streamedContent(), 3));
        $this->assertSame(__('ui.export.columns.tasks'), $taskRows[0]);
        $this->assertSame("'=Judul tugas", $taskRows[1][1]);
        $this->assertCount(2, $taskRows);
        $this->assertStringNotContainsString('Tugas rahasia', $tasks->streamedContent());

        $habits = $this->get(route('settings.exports', 'habits'))->assertOk();
        $habitRows = $this->parseCsv(substr($habits->streamedContent(), 3));
        $this->assertSame(__('ui.export.columns.habits'), $habitRows[0]);
        $this->assertSame("'@Kebiasaan", $habitRows[1][1]);
        $this->assertCount(2, $habitRows);
        $this->assertStringNotContainsString('Kebiasaan rahasia', $habits->streamedContent());

        $logs = $this->get(route('settings.exports', 'habit-logs'))->assertOk();
        $logRows = $this->parseCsv(substr($logs->streamedContent(), 3));
        $this->assertSame(__('ui.export.columns.habit_logs'), $logRows[0]);
        $this->assertSame("'+Catatan", $logRows[1][8]);
        $this->assertCount(2, $logRows);
        $this->assertStringNotContainsString('Catatan rahasia', $logs->streamedContent());
    }

    public function test_admin_is_forbidden_from_exporting_and_unknown_export_types_are_not_routed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.exports', 'tasks'))
            ->assertForbidden();

        $this->get(route('settings.exports', 'unknown'))->assertNotFound();
    }

    public function test_export_route_is_throttled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($request = 0; $request < 5; $request++) {
            $this->get(route('settings.exports', 'tasks'))->assertOk();
        }

        $this->get(route('settings.exports', 'tasks'))->assertTooManyRequests();
    }

    public function test_settings_page_shows_export_controls(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee(__('ui.export.title'))
            ->assertSee(__('ui.export.start_date'))
            ->assertSee(__('ui.export.end_date'))
            ->assertSee(route('settings.exports', 'transactions'));
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function parseCsv(string $content): array
    {
        $rows = [];

        foreach (explode("\r\n", $content) as $line) {
            if ($line !== '') {
                $rows[] = str_getcsv($line, ',', '"', '\\');
            }
        }

        return $rows;
    }
}
