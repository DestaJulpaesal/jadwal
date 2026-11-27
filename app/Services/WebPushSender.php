<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;
use Throwable;

class WebPushSender
{
    public function send(User $user, array $payload): void
    {
        $vapid = [
            'subject' => config('services.webpush.subject'),
            'publicKey' => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ];
        if (in_array('', $vapid, true) || in_array(null, $vapid, true)) {
            throw new RuntimeException('Web Push VAPID configuration is incomplete.');
        }

        $webPush = new WebPush(['VAPID' => $vapid], ['TTL' => 86_400]);
        $failed = false;
        $subscriptions = $user->pushSubscriptions()->get();

        foreach ($subscriptions as $storedSubscription) {
            try {
                $report = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $storedSubscription->endpoint,
                        'publicKey' => $storedSubscription->public_key,
                        'authToken' => $storedSubscription->auth_token,
                        'contentEncoding' => 'aes128gcm',
                    ]),
                    json_encode($payload, JSON_THROW_ON_ERROR)
                );
            } catch (Throwable $exception) {
                Log::warning('Push pengingat gagal dikirim.', [
                    'user_id' => $user->id,
                    'subscription_id' => $storedSubscription->id,
                    'exception' => $exception::class,
                ]);
                $failed = true;

                continue;
            }

            if ($report->isSuccess()) {
                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $storedSubscription->delete();

                continue;
            }

            Log::warning('Push pengingat ditolak layanan push.', [
                'user_id' => $user->id,
                'subscription_id' => $storedSubscription->id,
                'status' => $report->getResponse()?->getStatusCode(),
            ]);
            $failed = true;
        }

        if (! $user->pushSubscriptions()->exists()) {
            $user->forceFill(['push_enabled' => false])->save();
        }

        if ($failed) {
            throw new RuntimeException('One or more Web Push notifications failed.');
        }
    }
}
