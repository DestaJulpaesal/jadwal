<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_enable_and_disable_an_encrypted_push_subscription(): void
    {
        config([
            'services.webpush.public_key' => 'public-vapid-key',
            'services.webpush.private_key' => 'private-vapid-key',
        ]);
        $user = User::factory()->create();
        $endpoint = 'https://push.example.test/subscriptions/one';

        $this->actingAs($user)->postJson(route('settings.push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'client-public-key', 'auth' => 'client-auth-key'],
        ])->assertOk()->assertExactJson(['status' => 'subscribed']);

        $subscription = PushSubscription::query()->sole();
        $this->assertSame($endpoint, $subscription->endpoint);
        $this->assertSame('client-public-key', $subscription->public_key);
        $this->assertSame('client-auth-key', $subscription->auth_token);
        $this->assertTrue($user->fresh()->push_enabled);

        $this->actingAs($user)->deleteJson(route('settings.push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk()->assertExactJson(['status' => 'unsubscribed']);

        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->assertFalse($user->fresh()->push_enabled);
    }

    public function test_a_user_cannot_remove_another_users_subscription(): void
    {
        $firstUser = User::factory()->create(['push_enabled' => true]);
        $secondUser = User::factory()->create();
        $endpoint = 'https://push.example.test/subscriptions/private';
        $firstUser->pushSubscriptions()->create([
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'public_key' => 'client-public-key',
            'auth_token' => 'client-auth-key',
        ]);

        $this->actingAs($secondUser)->deleteJson(route('settings.push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertSame(1, $firstUser->pushSubscriptions()->count());
        $this->assertTrue($firstUser->fresh()->push_enabled);
    }

    public function test_push_subscriptions_are_rejected_when_the_server_has_no_vapid_keys(): void
    {
        config([
            'services.webpush.public_key' => null,
            'services.webpush.private_key' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.push-subscriptions.store'), [
                'endpoint' => 'https://push.example.test/subscriptions/one',
                'keys' => ['p256dh' => 'client-public-key', 'auth' => 'client-auth-key'],
            ])
            ->assertServiceUnavailable();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }
}
