<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\SaveGoalRequest;
use App\Http\Requests\SaveHabitRequest;
use App\Http\Requests\SaveTaskRequest;
use App\Support\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
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

    public function test_toast_has_the_correct_alert_semantics_icon_and_dismiss_action(): void
    {
        $html = Blade::render('<x-toast type="error" :action="[\'label\' => \'Urungkan\', \'url\' => \'/undo\']">Perubahan belum tersimpan.</x-toast>');

        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="assertive"', $html);
        $this->assertStringContainsString('#icon-galat', $html);
        $this->assertStringContainsString('aria-label="Tutup pemberitahuan"', $html);
        $this->assertStringContainsString('data-toast-duration="8000"', $html);
        $this->assertStringContainsString('data-toast-dismiss', $html);
        $this->assertStringContainsString('href="/undo"', $html);
    }

    public function test_feedback_host_provides_a_region_for_queued_toasts(): void
    {
        $html = Blade::render('<x-feedback-host />');

        $this->assertStringContainsString('data-toast-region', $html);
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

    public function test_each_form_request_returns_readable_indonesian_validation_errors(): void
    {
        $user = \App\Models\User::factory()->create();

        foreach ([
            LoginRequest::class,
            ProfileUpdateRequest::class,
            SaveGoalRequest::class,
            SaveHabitRequest::class,
            SaveTaskRequest::class,
        ] as $requestClass) {
            $request = new $requestClass;
            $request->setUserResolver(fn () => $user);
            $validator = Validator::make(
                [],
                app()->call([$request, 'rules']),
                $request->messages(),
                $request->attributes()
            );

            $this->assertTrue($validator->fails(), $requestClass.' must reject empty input.');
            foreach ($validator->errors()->all() as $message) {
                $this->assertDoesNotMatchRegularExpression(
                    '/validation\\.[a-z_.]+|The .+ field| is required\\.?/i',
                    $message,
                    $requestClass.' exposed an untranslated validation message.'
                );
            }
        }
    }

    public function test_disabled_account_message_is_only_shown_for_the_correct_password(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['credentials' => __('ui.login.failed')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['credentials' => __('ui.login.disabled')]);
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
