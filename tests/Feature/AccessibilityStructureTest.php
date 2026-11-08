<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AccessibilityStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_auth_user_and_admin_layouts_have_a_skip_link_target(): void
    {
        $this->assertSingleHeading($this->assertSkipLinkTarget($this->get('/')));
        $this->assertSingleHeading($this->assertSkipLinkTarget($this->get('/login')));
        $this->assertSingleHeading($this->assertSkipLinkTarget($this->get('/register')));

        $user = User::factory()->create();
        $this->assertSkipLinkTarget($this->actingAs($user)->get(route('user.dashboard')));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertSkipLinkTarget($this->actingAs($admin)->get(route('admin.dashboard')));
    }

    private function assertSkipLinkTarget(TestResponse $response): TestResponse
    {
        $response->assertOk()
            ->assertSee('class="skip-link" href="#main-content"', false)
            ->assertSee('id="main-content" tabindex="-1"', false);

        return $response;
    }

    private function assertSingleHeading(TestResponse $response): void
    {
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }
}
