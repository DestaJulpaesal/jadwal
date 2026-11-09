<?php

namespace Tests\Feature;

use App\Enums\HabitFrequency;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\SaveGoalRequest;
use App\Http\Requests\SaveHabitRequest;
use App\Http\Requests\SaveTaskRequest;
use App\Support\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
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
            $this->assertIndonesianValidationErrors($response, $routeName);
        }
    }

    public function test_search_validation_messages_are_kept_in_the_catalog(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get(route('user.search', ['q' => str_repeat('a', 101)]));

        $searchMessages = __('validation.catalog.search');

        $response->assertOk()->assertSee($searchMessages['q.max']);
        $this->assertStringNotContainsString('validation.catalog.search', $response->getContent());
    }

    public function test_habit_edit_action_uses_the_indonesian_verb(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->habits()->create([
            'title' => 'Jalan kaki',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('user.habits.index'))
            ->assertOk()
            ->assertSee('Ubah kebiasaan')
            ->assertDontSee('Edit kebiasaan');
    }

    public function test_inline_settings_profile_and_filter_validation_messages_are_indonesian(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $checks = [
            ['request' => fn () => $this->patch(route('settings.update'), []), 'bag' => 'default'],
            ['request' => fn () => $this->delete(route('profile.destroy'), []), 'bag' => 'userDeletion'],
            ['request' => fn () => $this->get(route('user.dashboard', ['year' => 'bukan-tahun'])), 'bag' => 'default'],
            ['request' => fn () => $this->get(route('user.calendar', ['month' => 'bukan-bulan'])), 'bag' => 'default'],
            ['request' => fn () => $this->get(route('user.tasks.index', ['status_id' => 'bukan-status'])), 'bag' => 'default'],
            ['request' => fn () => $this->get(route('user.reports.index', ['month' => 'bukan-bulan'])), 'bag' => 'default'],
        ];

        foreach ($checks as $check) {
            $response = $check['request']();
            $this->assertIndonesianValidationErrors($response, 'inline form or filter', $check['bag']);
        }

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'bukan-peran']));
        $this->assertIndonesianValidationErrors($response, 'admin filter');

        $response = $this->post(route('admin.categories.store'), []);
        $this->assertIndonesianValidationErrors($response, 'admin category form');
    }

    public function test_inline_action_validation_messages_are_indonesian(): void
    {
        $user = \App\Models\User::factory()->create();
        $habit = $user->habits()->create([
            'title' => 'Jalan kaki',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => now()->toDateString(),
        ]);
        $goal = $user->goals()->create([
            'title' => 'Baca buku',
            'type' => 'number',
            'target_value' => 10,
            'start_value' => 0,
            'status' => 'active',
        ]);
        $loan = $user->loans()->create([
            'direction' => 'lent',
            'counterparty' => 'Budi',
            'amount' => 100_000,
            'loan_date' => now()->toDateString(),
        ]);
        $obligation = $user->obligations()->create([
            'kind' => 'bill',
            'title' => 'Internet',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'remind_days_before' => 3,
        ]);
        $this->actingAs($user);

        $checks = [
            fn () => $this->post(route('user.habits.check-in', $habit), []),
            fn () => $this->delete(route('user.habits.check-in.cancel', $habit), []),
            fn () => $this->post(route('user.goals.updates.store', $goal), []),
            fn () => $this->post(route('user.loans.repay', $loan), []),
            fn () => $this->post(route('user.obligations.pay', $obligation), []),
        ];

        foreach ($checks as $check) {
            $response = $check();
            $response->assertSessionHasErrors();
            $this->assertIndonesianValidationErrors($response, 'inline action');
        }
    }

    public function test_inline_update_form_validation_messages_are_indonesian(): void
    {
        $user = \App\Models\User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => 'cash',
            'opening_balance' => 100_000,
        ]);
        $category = $user->moneyCategories()->create([
            'name' => 'Makan',
            'type' => 'expense',
        ]);
        $transaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'type' => 'expense',
            'amount' => 10_000,
            'occurred_on' => now()->toDateString(),
        ]);
        $obligation = $user->obligations()->create([
            'kind' => 'bill',
            'title' => 'Internet',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'remind_days_before' => 3,
        ]);
        $loan = $user->loans()->create([
            'direction' => 'lent',
            'counterparty' => 'Budi',
            'amount' => 100_000,
            'loan_date' => now()->toDateString(),
        ]);
        $this->actingAs($user);

        $checks = [
            fn () => $this->patch(route('user.money.wallets.update', $wallet), []),
            fn () => $this->patch(route('user.money.categories.update', $category), []),
            fn () => $this->patch(route('user.money.transactions.update', $transaction), []),
            fn () => $this->patch(route('user.obligations.update', $obligation), []),
            fn () => $this->patch(route('user.loans.update', $loan), []),
        ];

        foreach ($checks as $check) {
            $response = $check();
            $this->assertIndonesianValidationErrors($response, 'inline update form');
        }

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $adminCategory = \App\Models\Category::create([
            'user_id' => null,
            'name' => 'Olahraga',
            'color' => '#123456',
        ]);
        $response = $this->actingAs($admin)->patch(route('admin.categories.update', $adminCategory), []);
        $this->assertIndonesianValidationErrors($response, 'admin category update');
    }

    public function test_conditional_transfer_and_installment_validation_messages_are_indonesian(): void
    {
        $user = \App\Models\User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => 'cash',
            'opening_balance' => 1_000_000,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('user.money.transactions.store'), [
            'type' => 'transfer',
            'amount' => '100.000',
            'wallet_id' => $wallet->id,
            'occurred_on' => now()->toDateString(),
        ]);
        $response->assertSessionHasErrors(['to_wallet_id']);
        $this->assertIndonesianValidationErrors($response, 'transaction transfer');

        $response = $this->post(route('user.obligations.store'), [
            'kind' => 'installment',
            'title' => 'Cicilan',
            'amount' => '100.000',
            'due_day' => 15,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'remind_days_before' => 3,
        ]);
        $response->assertSessionHasErrors(['installments_total']);
        $this->assertIndonesianValidationErrors($response, 'installment form');
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

    private function assertIndonesianValidationErrors(
        TestResponse $response,
        string $context,
        string $bag = 'default'
    ): void {
        $errors = $response->getSession()->get('errors');
        $messages = $errors?->getBag($bag)->all() ?? [];

        $this->assertNotEmpty($messages, "Expected validation messages for {$context}.");

        foreach ($messages as $message) {
            $this->assertDoesNotMatchRegularExpression(
                '/validation\\.[a-z_.]+|The .+ field| is required\\.?/i',
                $message,
                "{$context} exposed an untranslated validation message."
            );
        }
    }
}
