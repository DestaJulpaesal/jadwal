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

    public function test_global_focus_and_scroll_styles_respect_theme_and_reduced_motion(): void
    {
        $baseStyles = (string) file_get_contents(resource_path('css/base.css'));
        $tokens = (string) file_get_contents(resource_path('css/tokens.css'));

        $this->assertStringContainsString('outline: 3px solid var(--color-focus);', $baseStyles);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $baseStyles);
        $this->assertStringContainsString('scroll-behavior: auto;', $baseStyles);
        $this->assertStringContainsString('--color-focus: #226b50;', $tokens);
        $this->assertStringContainsString('--color-focus: #b7d4c2;', $tokens);

        foreach ([
            'components/buttons.css',
            'components/legacy.css',
            'pages/home.css',
            'pages/login.css',
        ] as $stylesheet) {
            $this->assertStringContainsString(
                'outline: 3px solid var(--color-focus);',
                (string) file_get_contents(resource_path('css/'.$stylesheet))
            );
        }
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
