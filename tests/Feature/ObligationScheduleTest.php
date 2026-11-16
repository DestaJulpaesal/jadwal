<?php

namespace Tests\Feature;

use App\Enums\ObligationKind;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\ObligationSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObligationScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_installment_periods_respect_start_date_payments_and_total_count(): void
    {
        $user = User::factory()->create();
        $obligation = $user->obligations()->create([
            'kind' => ObligationKind::Installment,
            'title' => 'Cicilan tiga kali',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => '2026-01-20',
            'installments_total' => 3,
            'remind_days_before' => 3,
        ]);
        $user->obligationPayments()->create([
            'obligation_id' => $obligation->id,
            'due_on' => '2026-02-15',
            'paid_on' => '2026-02-15',
            'amount_paid' => 100_000,
        ]);

        $schedule = app(ObligationSchedule::class);
        $dates = $schedule->duePeriods($obligation, '2026-01-01', '2026-12-31');

        $this->assertSame(
            ['2026-03-15', '2026-04-15'],
            array_map(fn ($date) => $date->toDateString(), $dates)
        );

        foreach (['2026-03-15', '2026-04-15'] as $date) {
            $user->obligationPayments()->create([
                'obligation_id' => $obligation->id,
                'due_on' => $date,
                'paid_on' => $date,
                'amount_paid' => 100_000,
            ]);
        }

        $obligation->unsetRelation('payments');
        $this->assertSame([], $schedule->duePeriods($obligation, '2026-05-01', '2026-12-31'));
    }

    public function test_agenda_shows_one_overdue_row_per_obligation_and_honors_reminder_days(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-16 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $overdue = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Tagihan tertunggak',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => '2026-09-01',
            'remind_days_before' => 3,
        ]);
        $longReminder = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Tagihan dengan pengingat panjang',
            'amount' => 100_000,
            'due_day' => 20,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 5,
        ]);
        $shortReminder = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Tagihan tanpa pengingat awal',
            'amount' => 100_000,
            'due_day' => 20,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 3,
        ]);
        $dueToday = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Tagihan jatuh tempo hari ini',
            'amount' => 100_000,
            'due_day' => 16,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 0,
        ]);
        $longOverdue = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Tagihan lama',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => '2020-01-01',
            'remind_days_before' => 3,
        ]);

        $items = app(AgendaService::class)->attentionFor($user, '2026-11-16', 3);

        $this->assertCount(4, $items);
        $this->assertSame(
            'Terlambat 3 periode',
            $items->firstWhere('title', $overdue->title)['status']
        );
        $this->assertSame(
            'Jatuh tempo 4 hari lagi',
            $items->firstWhere('title', $longReminder->title)['status']
        );
        $this->assertNull($items->firstWhere('title', $shortReminder->title));
        $this->assertSame(
            'Jatuh tempo hari ini',
            $items->firstWhere('title', $dueToday->title)['status']
        );
        $this->assertSame(
            'Terlambat 12+ periode',
            $items->firstWhere('title', $longOverdue->title)['status']
        );
    }

    public function test_completed_installments_are_absent_from_future_agenda_and_money_plans(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-04-15 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $obligation = $user->obligations()->create([
            'kind' => ObligationKind::Installment,
            'title' => 'Cicilan selesai',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => '2026-01-01',
            'installments_total' => 3,
            'remind_days_before' => 3,
        ]);

        foreach (['2026-01-15', '2026-02-15', '2026-03-15'] as $date) {
            $user->obligationPayments()->create([
                'obligation_id' => $obligation->id,
                'due_on' => $date,
                'paid_on' => $date,
                'amount_paid' => 100_000,
            ]);
        }

        $agenda = app(AgendaService::class);
        $this->assertTrue($agenda->forRange($user, '2026-04-01', '2026-06-30')->flatten(1)->isEmpty());
        $this->assertTrue($agenda->attentionFor($user, '2026-04-15')->isEmpty());

        $this->actingAs($user)
            ->get(route('user.money.index', ['month' => '2026-04']))
            ->assertOk()
            ->assertDontSee('Cicilan selesai');
    }
}
