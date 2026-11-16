<?php

namespace Tests\Feature;

use App\Enums\ObligationKind;
use App\Mail\DailyDigestMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_digest_uses_the_users_selected_time_and_days(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'digest_enabled' => true,
            'digest_time' => '07:00',
            'digest_days' => [1, 3, 5],
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 00:00:00', 'UTC'));
        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertSent(DailyDigestMail::class, fn (DailyDigestMail $mail) => $mail->hasTo($user->email));

        Mail::fake();
        $user->forceFill(['digest_last_sent_on' => null])->save();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-03 00:00:00', 'UTC'));
        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_daily_digest_is_not_sent_before_the_selected_local_time(): void
    {
        Mail::fake();
        User::factory()->create([
            'digest_enabled' => true,
            'digest_time' => '08:30',
            'digest_days' => [1, 2, 3, 4, 5, 6, 7],
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 00:00:00', 'UTC'));
        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_daily_digest_omits_installments_that_are_fully_paid(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'digest_enabled' => true,
            'digest_time' => '07:00',
            'digest_days' => [1, 2, 3, 4, 5, 6, 7],
            'timezone' => 'UTC',
        ]);
        $obligation = $user->obligations()->create([
            'kind' => ObligationKind::Installment,
            'title' => 'Cicilan yang sudah lunas',
            'amount' => 100_000,
            'due_day' => 2,
            'starts_on' => '2026-08-01',
            'installments_total' => 3,
            'remind_days_before' => 3,
        ]);

        foreach (['2026-08-02', '2026-09-02', '2026-10-02'] as $date) {
            $user->obligationPayments()->create([
                'obligation_id' => $obligation->id,
                'due_on' => $date,
                'paid_on' => $date,
                'amount_paid' => 100_000,
            ]);
        }

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 07:30:00', 'UTC'));
        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertSent(
            DailyDigestMail::class,
            fn (DailyDigestMail $mail) => $mail->hasTo($user->email) && $mail->attentionItems === []
        );
    }
}
