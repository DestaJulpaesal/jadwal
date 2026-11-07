<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\SaveGoalRequest;
use App\Http\Requests\SaveHabitRequest;
use App\Http\Requests\SaveTaskRequest;
use App\Support\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_helper_flashes_a_structured_payload(): void
    {
        $this->get('/')
            ->assertOk();

        $response = Feedback::success(redirect('/'), 'Berhasil disimpan.', [
            'label' => 'Urungkan',
            'url' => '/undo',
        ]);

        $this->assertSame('success', $response->getSession()->get('feedback.type'));
        $this->assertSame('Berhasil disimpan.', $response->getSession()->get('feedback.message'));
        $this->assertSame(['label' => 'Urungkan', 'url' => '/undo'], $response->getSession()->get('feedback.action'));
    }

    public function test_each_form_request_defines_indonesian_messages_and_attributes(): void
    {
        foreach ([
            LoginRequest::class,
            ProfileUpdateRequest::class,
            SaveGoalRequest::class,
            SaveHabitRequest::class,
            SaveTaskRequest::class,
        ] as $requestClass) {
            $request = new $requestClass;

            $this->assertNotEmpty($request->messages(), $requestClass.' must define messages.');
            $this->assertNotEmpty($request->attributes(), $requestClass.' must define attributes.');
        }
    }

    public function test_login_failure_copy_does_not_disclose_which_credential_was_wrong(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['credentials' => __('ui.login.failed')]);
    }

    public function test_login_required_and_email_format_messages_are_indonesian(): void
    {
        $this->post(route('login'), [])
            ->assertSessionHasErrors([
                'email' => 'Email belum diisi.',
                'password' => 'Kata sandi belum diisi.',
            ]);

        $this->post(route('login'), [
            'email' => 'not-an-email',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Format email belum benar. Contoh: nama@email.com',
        ]);
    }
}
