<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginIpRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_attempts_are_limited_per_ip_across_distinct_emails(): void
    {
        for ($attempt = 1; $attempt <= 21; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
                ->post(route('login'), [
                    'email' => "attempt-{$attempt}@example.test",
                    'password' => 'WrongPassword123',
                ]);

            if ($attempt < 21) {
                $response->assertSessionHasErrors('credentials');
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
