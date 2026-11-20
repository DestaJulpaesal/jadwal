<?php

namespace Tests\Feature;

use App\Enums\ObligationKind;
use App\Mail\DailyDigestMail;
use App\Models\User;
use App\Services\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $user->tasks()->create(['title' => 'Agenda', 'starts_at' => '2026-11-02 09:00:00']);

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
        $user->tasks()->create(['title' => 'Agenda', 'starts_at' => '2026-11-02 09:00:00']);

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

    public function test_digest_mail_failure_does_not_block_other_users_and_retries_are_capped(): void
    {
        Cache::flush();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 08:00:00', 'UTC'));
        $first = User::factory()->create(['digest_enabled' => true, 'timezone' => 'UTC']);
        $second = User::factory()->create(['digest_enabled' => true, 'timezone' => 'UTC']);
        foreach ([$first, $second] as $user) {
            $user->tasks()->create([
                'title' => 'Agenda',
                'starts_at' => '2026-11-02 09:00:00',
            ]);
        }

        $failingRecipient = new class {
            public function send($message): void
            {
                throw new \RuntimeException('SMTP unavailable');
            }
        };
        $successfulRecipient = new class {
            public int $sent = 0;

            public function send($message): void
            {
                $this->sent++;
            }
        };
        Mail::shouldReceive('to')->times(3)->with($first->email)->andReturn($failingRecipient);
        Mail::shouldReceive('to')->once()->with($second->email)->andReturn($successfulRecipient);
        Log::shouldReceive('warning')->times(3)->with('Digest gagal', \Mockery::on(
            fn (array $context) => $context['user_id'] === $first->id && $context['exception'] === \RuntimeException::class
        ));

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->artisan('app:send-daily-digest')->assertSuccessful();
        }

        $this->assertSame(1, $successfulRecipient->sent);
        $this->assertNull($first->fresh()->digest_last_sent_on);
        $this->assertSame('2026-11-02', $second->fresh()->digest_last_sent_on->toDateString());
    }

    public function test_push_digest_uses_the_shared_schedule_and_is_independent_of_email_failure(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 08:00:00', 'UTC'));
        $user = User::factory()->create([
            'digest_enabled' => true,
            'push_enabled' => true,
            'digest_time' => '07:00',
            'digest_days' => [1, 2, 3, 4, 5, 6, 7],
            'timezone' => 'UTC',
        ]);
        $user->pushSubscriptions()->create([
            'endpoint_hash' => hash('sha256', 'https://push.example.test/subscriptions/reminder'),
            'endpoint' => 'https://push.example.test/subscriptions/reminder',
            'public_key' => 'client-public-key',
            'auth_token' => 'client-auth-key',
        ]);
        $user->tasks()->create([
            'title' => 'Agenda',
            'starts_at' => '2026-11-02 09:00:00',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new \RuntimeException('SMTP unavailable'));
        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->with('Digest gagal', \Mockery::on(
                fn (array $context) => $context['user_id'] === $user->id
                    && $context['exception'] === \RuntimeException::class
            ));

        $sender = \Mockery::mock(WebPushSender::class);
        $sender->shouldReceive('send')->once()->withArgs(
            fn (User $recipient, array $payload) => $recipient->is($user)
                && $payload['title'] === __('ui.push.reminder_title')
                && $payload['body'] === __('ui.push.reminder_body', [
                    'tasks' => 1,
                    'habits' => 0,
                    'attention' => 0,
                ])
                && $payload['url'] === '/dashboard'
        );
        $this->app->instance(WebPushSender::class, $sender);

        $this->artisan('app:send-daily-digest')->assertSuccessful();

        $this->assertNull($user->fresh()->digest_last_sent_on);
        $this->assertSame('2026-11-02', $user->fresh()->push_last_sent_on->toDateString());
    }

    public function test_digest_is_not_sent_when_there_are_no_items(): void
    {
        Mail::fake();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 08:00:00', 'UTC'));
        $user = User::factory()->create(['digest_enabled' => true, 'timezone' => 'UTC']);

        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame('2026-11-02', $user->fresh()->digest_last_sent_on->toDateString());
    }
}
