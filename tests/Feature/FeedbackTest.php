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

    public function test_inline_validation_fields_have_indonesian_attribute_names(): void
    {
        $attributes = __('validation.attributes');

        foreach ([
            'amount', 'amount_paid', 'avatar', 'category_color', 'category_id',
            'category_name', 'color', 'confirmation', 'counterparty',
            'current_password', 'dashboard_sections', 'dashboard_sections.*',
            'description', 'digest_days', 'digest_days.*', 'digest_enabled',
            'digest_time', 'direction', 'due', 'due_date', 'due_day', 'due_on',
            'duration_minutes', 'email', 'ends_at', 'ends_on', 'frequency',
            'goal_unit_other', 'goal_value', 'habit_id', 'hide_financial_amounts',
            'installments_total', 'is_active', 'kind', 'loan_date', 'log_date',
            'money_category_id', 'month', 'name', 'note', 'occurred_on',
            'opening_balance', 'paid_on', 'password', 'password_confirmation',
            'performed_at', 'q', 'query', 'recorded_on', 'remember', 'remind_days_before',
            'remove_avatar', 'role', 'schedule_times', 'schedule_times.*', 'search',
            'section', 'starts_at', 'starts_on', 'status', 'status_id',
            'task_status_id', 'theme', 'timezone', 'title', 'to_wallet_id', 'token',
            'type', 'value', 'wallet_id', 'week_starts_on', 'weekdays', 'weekdays.*',
            'weekly_target', 'year',
        ] as $field) {
            $this->assertArrayHasKey($field, $attributes, "Missing Indonesian label for {$field}.");
            $this->assertNotSame('', $attributes[$field], "Empty Indonesian label for {$field}.");
        }
    }

    public function test_inline_create_form_validation_messages_are_indonesian(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        foreach ([
            'user.money.wallets.store',
            'user.money.categories.store',
            'user.money.transactions.store',
            'user.obligations.store',
            'user.loans.store',
        ] as $routeName) {
            $response = $this->post(route($routeName), []);

            $response->assertSessionHasErrors();
            $messages = $response->getSession()->get('errors')->getBag('default')->all();
            $this->assertNotEmpty($messages, "Expected validation messages for {$routeName}.");

            foreach ($messages as $message) {
                $this->assertDoesNotMatchRegularExpression(
                    '/validation\\.[a-z_.]+|The .+ field| is required\\.?/i',
                    $message,
                    "{$routeName} exposed an untranslated validation message."
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
