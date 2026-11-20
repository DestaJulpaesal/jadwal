<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\WebPushSender;
use App\Support\UserClock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyDigest extends Command
{
    protected $signature = 'app:send-daily-digest';

    protected $description = 'Kirim ringkasan harian kepada pengguna yang mengaktifkannya';

    public function handle(AgendaService $agenda, UserClock $clock, WebPushSender $pushSender): int
    {
        User::query()->where('is_active', true)->where(function ($query) {
            $query->where('digest_enabled', true)
                ->orWhere(fn ($query) => $query->where('push_enabled', true)->whereHas('pushSubscriptions'));
        })->chunkById(100, function ($users) use ($agenda, $clock, $pushSender) {
            foreach ($users as $user) {
                $localNow = Carbon::now($user->timezone);
                $today = $clock->date($user);
                $sendEmail = $user->digest_enabled
                    && $user->digest_last_sent_on?->toDateString() !== $today;
                $sendPush = $user->push_enabled
                    && $user->pushSubscriptions()->exists()
                    && $user->push_last_sent_on?->toDateString() !== $today;
                if (
                    $localNow->format('H:i') < ($user->digest_time ?: '07:00')
                    || ! in_array($localNow->dayOfWeekIso, $user->digest_days ?? range(1, 7), true)
                    || (! $sendEmail && ! $sendPush)
                ) {
                    continue;
                }

                try {
                    $items = $agenda->forDate($user, $today);
                    $attention = $agenda->attentionFor($user, $today, 3)->all();
                    $tasks = $items->where('type', 'task')->all();
                    $habits = $items->where('type', 'habit')->all();
                } catch (Throwable $exception) {
                    foreach (['daily-digest' => $sendEmail, 'daily-push' => $sendPush] as $channel => $enabled) {
                        if (! $enabled) {
                            continue;
                        }
                        $retryKey = "{$channel}:failures:{$user->id}:{$today}";
                        Cache::add($retryKey, 0, $localNow->copy()->endOfDay()->utc());
                        Cache::increment($retryKey);
                    }
                    Log::warning('Agenda pengingat gagal disiapkan.', [
                        'user_id' => $user->id,
                        'exception' => $exception::class,
                    ]);

                    continue;
                }

                if ($tasks === [] && $habits === [] && $attention === []) {
                    if ($sendEmail) {
                        $user->forceFill(['digest_last_sent_on' => $today])->save();
                        Cache::forget("daily-digest:failures:{$user->id}:{$today}");
                    }
                    if ($sendPush) {
                        $user->forceFill(['push_last_sent_on' => $today])->save();
                    }

                    continue;
                }

                if ($sendEmail) {
                    $retryKey = "daily-digest:failures:{$user->id}:{$today}";
                    if (Cache::get($retryKey, 0) < 3) {
                        try {
                            Mail::to($user->email)->send(new DailyDigestMail(
                                $user->name,
                                $localNow->translatedFormat('l, j F Y'),
                                $tasks,
                                $habits,
                                $attention
                            ));
                            $user->forceFill(['digest_last_sent_on' => $today])->save();
                            Cache::forget($retryKey);
                            $this->info('Ringkasan harian berhasil dikirim.');
                        } catch (Throwable $exception) {
                            Cache::add($retryKey, 0, $localNow->copy()->endOfDay()->utc());
                            Cache::increment($retryKey);
                            Log::warning('Digest gagal', [
                                'user_id' => $user->id,
                                'exception' => $exception::class,
                            ]);
                        }
                    }
                }

                if ($sendPush) {
                    $retryKey = "daily-push:failures:{$user->id}:{$today}";
                    if (Cache::get($retryKey, 0) >= 3) {
                        continue;
                    }

                    try {
                        $pushSender->send($user, [
                            'title' => $attention === []
                                ? __('ui.push.reminder_title')
                                : __('ui.push.attention_title'),
                            'body' => __('ui.push.reminder_body', [
                                'tasks' => count($tasks),
                                'habits' => count($habits),
                                'attention' => count($attention),
                            ]),
                            'url' => '/dashboard',
                        ]);
                        $user->forceFill(['push_last_sent_on' => $today])->save();
                        Cache::forget($retryKey);
                        $this->info('Pengingat push berhasil dikirim.');
                    } catch (Throwable $exception) {
                        Cache::add($retryKey, 0, $localNow->copy()->endOfDay()->utc());
                        Cache::increment($retryKey);
                        Log::warning('Push pengingat gagal', [
                            'user_id' => $user->id,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            }
        });

        return self::SUCCESS;
    }
}
