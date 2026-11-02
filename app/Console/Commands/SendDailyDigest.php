<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Models\User;
use App\Services\AgendaService;
use App\Support\UserClock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest extends Command
{
    protected $signature = 'app:send-daily-digest';

    protected $description = 'Kirim ringkasan harian kepada pengguna yang mengaktifkannya';

    public function handle(AgendaService $agenda, UserClock $clock): int
    {
        User::query()->where('is_active', true)->where('digest_enabled', true)->chunkById(100, function ($users) use ($agenda, $clock) {
            foreach ($users as $user) {
                $localNow = Carbon::now($user->timezone);
                $today = $clock->date($user);
                if (
                    $localNow->format('H:i') < ($user->digest_time ?: '07:00')
                    || ! in_array($localNow->dayOfWeekIso, $user->digest_days ?? range(1, 7), true)
                    || $user->digest_last_sent_on?->toDateString() === $today
                ) {
                    continue;
                }

                $items = $agenda->forDate($user, $today);
                $attention = $agenda->attentionFor($user, $today, 3)->all();
                Mail::to($user->email)->send(new DailyDigestMail(
                    $user->name,
                    $localNow->translatedFormat('l, j F Y'),
                    $items->where('type', 'task')->all(),
                    $items->where('type', 'habit')->all(),
                    $attention
                ));
                $user->forceFill(['digest_last_sent_on' => $today])->save();
                $this->info("Ringkasan harian dikirim ke {$user->email}.");
            }
        });

        return self::SUCCESS;
    }
}
