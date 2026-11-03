<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('rel="icon" type="image/svg+xml"', false)
            ->assertSee('favicon.svg?v=3', false)
            ->assertSee('Satu langkah kecil bisa bikin harimu lebih tertata.')
            ->assertSee('footer-links')
            ->assertSee('Daftar gratis');
    }
}
