<?php

namespace Tests\Feature;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_only_shows_the_authenticated_users_tasks(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 01:00:00', 'UTC'));
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        $user->tasks()->create([
            'title' => 'My task',
            'starts_at' => '2026-10-15 02:00:00',
            'ends_at' => '2026-10-15 03:00:00',
        ]);
        $otherUser->tasks()->create([
            'title' => 'Private task',
            'starts_at' => '2026-10-15 02:00:00',
            'ends_at' => '2026-10-15 03:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard'));

        $response->assertOk()
            ->assertSee('My task')
            ->assertDontSee('Private task')
            ->assertSee('Total tugas')
            ->assertSee('Kalender')
            ->assertSee(route('user.calendar'), false)
            ->assertSee('Jadwal hari ini')
            ->assertSee('7 hari mendatang')
            ->assertSee('Ringkasan')
            ->assertSee('Aktivitas')
            ->assertSee('Keuangan')
            ->assertSee('Perencanaan')
            ->assertSee('class="concept-quick-link"><svg', false);
    }

    public function test_calendar_page_is_displayed_separately_from_the_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('user.calendar', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertSee('id="user-calendar"', false)
            ->assertSee('Oktober 2026');
    }

    public function test_user_can_create_a_task_with_a_new_category_and_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $status = TaskStatus::create(['name' => 'Belum dimulai']);

        $response = $this->actingAs($user)->post(route('user.tasks.store'), [
            'title' => 'Belajar Laravel',
            'description' => 'Selesaikan latihan',
            'category_name' => 'Belajar',
            'category_color' => '#22AA44',
            'task_status_id' => $status->id,
            'due_date' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('user.tasks.index'));
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Belajar',
            'color' => '#22AA44',
        ]);
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Belajar Laravel',
            'task_status_id' => $status->id,
        ]);
    }

    public function test_user_can_open_task_create_and_edit_forms(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = $user->tasks()->create(['title' => 'Task to edit']);

        $this->actingAs($user)
            ->get(route('user.tasks.create'))
            ->assertOk()
            ->assertSee('Tambah tugas')
            ->assertSee('Jatuh tempo')
            ->assertSee('Waktu mulai')
            ->assertSee('Waktu selesai')
            ->assertSee('mobileNavigationMenu');

        $this->get(route('user.tasks.edit', $task))
            ->assertOk()
            ->assertSee('Task to edit')
            ->assertSee('Simpan perubahan');
    }

    public function test_invalid_task_filter_has_an_indonesian_message(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('user.tasks.index', ['due' => 'someday']))
            ->assertSessionHasErrors([
                'due' => 'Pilihan jatuh tempo tidak valid.',
            ]);
    }

    public function test_user_can_create_a_task_without_a_category(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('user.tasks.store'), [
            'title' => 'Tugas tanpa kategori',
        ]);

        $response->assertRedirect(route('user.tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Tugas tanpa kategori',
            'category_id' => null,
        ]);
    }

    public function test_user_can_update_and_delete_their_own_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $task = $user->tasks()->create(['title' => 'Judul awal']);

        $this->actingAs($user)
            ->put(route('user.tasks.update', $task), ['title' => 'Judul baru'])
            ->assertRedirect(route('user.tasks.index'));

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Judul baru']);

        $this->delete(route('user.tasks.destroy', $task))
            ->assertRedirect(route('user.tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_can_quickly_mark_their_own_task_as_completed(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create(['title' => 'Selesaikan laporan']);
        $completedStatus = TaskStatus::create(['name' => 'Selesai']);

        $this->actingAs($user)
            ->from(route('user.tasks.index'))
            ->patch(route('user.tasks.complete', $task))
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('feedback.message', 'Tugas berhasil ditandai selesai.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => $completedStatus->id,
        ]);
    }

    public function test_quick_completion_reports_when_the_completed_status_is_missing(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create(['title' => 'Tugas tanpa status selesai']);

        $this->actingAs($user)
            ->from(route('user.tasks.index'))
            ->patch(route('user.tasks.complete', $task))
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('feedback.message', 'Status “Selesai” belum tersedia. Hubungi admin.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => null,
        ]);
    }

    public function test_user_cannot_quickly_complete_another_users_task(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $task = $owner->tasks()->create(['title' => 'Tugas pribadi']);
        TaskStatus::create(['name' => 'Selesai']);

        $this->actingAs($user)
            ->patch(route('user.tasks.complete', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => null,
        ]);
    }

    public function test_dashboard_groups_schedules_by_local_day_and_orders_them_by_time(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 01:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $user->tasks()->create([
            'title' => 'Pagi',
            'starts_at' => '2026-10-15 02:00:00',
            'ends_at' => '2026-10-15 03:00:00',
        ]);
        $user->tasks()->create([
            'title' => 'Sore',
            'starts_at' => '2026-10-15 08:00:00',
            'ends_at' => '2026-10-15 09:00:00',
        ]);
        $user->tasks()->create([
            'title' => 'Tenggat hari ini',
            'due_date' => '2026-10-15 10:00:00',
        ]);
        $user->tasks()->create([
            'title' => 'Sudah lewat',
            'due_date' => '2026-10-14 08:00:00',
        ]);
        $user->tasks()->create([
            'title' => 'Pekan depan',
            'starts_at' => '2026-10-16 02:00:00',
            'ends_at' => '2026-10-16 03:00:00',
        ]);
        $completedStatus = TaskStatus::create(['name' => 'Selesai']);
        $user->tasks()->create([
            'title' => 'Sudah selesai',
            'task_status_id' => $completedStatus->id,
            'starts_at' => '2026-10-16 04:00:00',
            'ends_at' => '2026-10-16 05:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertViewHas('todayTasks', fn ($tasks) => $tasks->pluck('title')->all() === [
                'Pagi',
                'Sore',
                'Tenggat hari ini',
            ])
            ->assertViewHas('overdueTasks', fn ($tasks) => $tasks->pluck('title')->all() === ['Sudah lewat'])
            ->assertViewHas('upcomingTasks', fn ($tasks) => $tasks->pluck('title')->all() === ['Pekan depan']);
    }

    public function test_schedule_times_are_stored_in_utc_and_shown_in_the_users_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

        $this->actingAs($user)
            ->post(route('user.tasks.store'), [
                'title' => 'Jadwal rapat',
                'starts_at' => '2026-10-15T10:00',
                'ends_at' => '2026-10-15T11:00',
                'due_date' => '2026-10-15T09:00',
            ])
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHasNoErrors();

        $task = $user->tasks()->firstOrFail();
        $this->assertSame('2026-10-15 03:00:00', $task->getRawOriginal('starts_at'));
        $this->assertSame('2026-10-15 04:00:00', $task->getRawOriginal('ends_at'));
        $this->assertSame('2026-10-15 02:00:00', $task->getRawOriginal('due_date'));

        $this->get(route('user.tasks.edit', $task))
            ->assertOk()
            ->assertSee('value="2026-10-15T10:00"', false)
            ->assertSee('value="2026-10-15T11:00"', false);
    }

    public function test_schedule_end_cannot_be_before_start(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('user.tasks.create'))
            ->post(route('user.tasks.store'), [
                'title' => 'Waktu tidak valid',
                'starts_at' => '2026-10-15T11:00',
                'ends_at' => '2026-10-15T10:00',
            ])
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseMissing('tasks', ['user_id' => $user->id, 'title' => 'Waktu tidak valid']);
    }

    public function test_schedule_conflicts_warn_without_blocking_and_only_check_the_owners_tasks(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $otherUser = User::factory()->create();
        $user->tasks()->create([
            'title' => 'Jadwal yang sudah ada',
            'starts_at' => '2026-10-15 03:00:00',
            'ends_at' => '2026-10-15 04:00:00',
        ]);
        $otherUser->tasks()->create([
            'title' => 'Jadwal pengguna lain',
            'starts_at' => '2026-10-15 03:00:00',
            'ends_at' => '2026-10-15 04:00:00',
        ]);

        $response = $this->actingAs($user)->post(route('user.tasks.store'), [
            'title' => 'Jadwal baru yang bentrok',
            'starts_at' => '2026-10-15T10:30',
            'ends_at' => '2026-10-15T11:30',
        ]);

        $response->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('schedule_conflicts', function (array $conflicts) {
                return count($conflicts) === 1
                    && $conflicts[0]['title'] === 'Jadwal yang sudah ada';
            });

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Jadwal baru yang bentrok']);

        $this->get(route('user.tasks.index'))
            ->assertSee('jadwal berhasil disimpan')
            ->assertSee('Jadwal yang sudah ada')
            ->assertDontSee('Jadwal pengguna lain');
    }

    public function test_user_cannot_access_another_users_task(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $owner = User::factory()->create();
        $task = $owner->tasks()->create(['title' => 'Private task']);

        $this->actingAs($user)->get(route('user.tasks.edit', $task))->assertForbidden();
        $this->put(route('user.tasks.update', $task), ['title' => 'Changed'])->assertForbidden();
        $this->delete(route('user.tasks.destroy', $task))->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Private task']);
    }

    public function test_user_cannot_assign_another_users_category(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        $category = $otherUser->categories()->create(['name' => 'Private']);

        $response = $this->actingAs($user)->from(route('user.tasks.create'))->post(route('user.tasks.store'), [
            'title' => 'Test task',
            'category_id' => $category->id,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('tasks', ['user_id' => $user->id, 'title' => 'Test task']);
    }

    public function test_task_search_and_status_filter_are_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        $status = TaskStatus::create(['name' => 'In progress']);
        $user->tasks()->create([
            'title' => 'Prepare weekly report',
            'task_status_id' => $status->id,
        ]);
        $user->tasks()->create(['title' => 'Prepare personal notes']);
        $otherUser->tasks()->create([
            'title' => 'Prepare confidential report',
            'task_status_id' => $status->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.tasks.index', [
            'search' => 'report',
            'status_id' => $status->id,
        ]));

        $response->assertOk()
            ->assertSee('Prepare weekly report')
            ->assertDontSee('Prepare personal notes')
            ->assertDontSee('Prepare confidential report');
    }

    public function test_calendar_shows_only_the_authenticated_users_tasks_in_the_selected_month(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        $user->tasks()->create([
            'title' => 'My October deadline',
            'due_date' => '2026-10-15 09:30:00',
        ]);
        $otherUser->tasks()->create([
            'title' => 'Private October deadline',
            'due_date' => '2026-10-15 10:30:00',
        ]);
        $user->tasks()->create([
            'title' => 'My November deadline',
            'due_date' => '2026-11-15 09:30:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.calendar', [
            'year' => 2026,
            'month' => 10,
        ]));

        $response->assertOk()
            ->assertSee('My October deadline')
            ->assertDontSee('Private October deadline')
            ->assertDontSee('My November deadline')
            ->assertSee('Oktober 2026')
            ->assertSee('id="user-calendar"', false);
    }

    public function test_calendar_shows_scheduled_times_in_local_time_order(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $user->tasks()->create([
            'title' => 'Jadwal pagi',
            'starts_at' => '2026-10-14 18:30:00',
            'ends_at' => '2026-10-14 19:30:00',
        ]);
        $user->tasks()->create([
            'title' => 'Jadwal sore',
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.calendar', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertSeeInOrder(['01:30–02:30', 'Jadwal pagi', '17:00–18:00', 'Jadwal sore']);
    }

    public function test_category_colors_are_shown_in_the_task_list_and_calendar(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $category = $user->categories()->create([
            'name' => 'Belajar',
            'color' => '#22AA44',
        ]);
        $user->tasks()->create([
            'title' => 'Latihan',
            'category_id' => $category->id,
            'starts_at' => '2026-10-15 03:00:00',
            'ends_at' => '2026-10-15 04:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.tasks.index'))
            ->assertOk()
            ->assertSee('background-color: #22AA44', false)
            ->assertSee('Belajar');

        $this->get(route('user.calendar', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertSee('background-color: #22AA44', false)
            ->assertSee('Latihan');
    }

    public function test_due_date_filter_supports_today_upcoming_overdue_and_no_due_date(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->tasks()->create(['title' => 'Due today', 'due_date' => now()->addHour()]);
        $user->tasks()->create(['title' => 'Due later', 'due_date' => now()->addDay()]);
        $user->tasks()->create(['title' => 'Already overdue', 'due_date' => now()->subDay()]);
        $user->tasks()->create(['title' => 'No due date']);

        $this->actingAs($user)
            ->get(route('user.tasks.index', ['due' => 'today']))
            ->assertSee('Due today')
            ->assertDontSee('Due later')
            ->assertDontSee('Already overdue');

        $this->get(route('user.tasks.index', ['due' => 'upcoming']))
            ->assertSee('Due later')
            ->assertDontSee('Due today');

        $this->get(route('user.tasks.index', ['due' => 'overdue']))
            ->assertSee('Already overdue')
            ->assertDontSee('Due later');

        $this->get(route('user.tasks.index', ['due' => 'none']))
            ->assertSee('No due date')
            ->assertDontSee('Due today');
    }

    public function test_due_date_filter_uses_the_users_local_day(): void
    {
        $this->travelTo(now()->parse('2026-10-15 02:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $user->tasks()->create([
            'title' => 'Late local evening',
            'due_date' => '2026-10-15 06:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.tasks.index', ['due' => 'today']))
            ->assertOk()
            ->assertSee('Late local evening');
    }

    public function test_overdue_filter_excludes_completed_tasks_and_includes_elapsed_schedule_end(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $complete = TaskStatus::create(['name' => 'Selesai']);
        $user->tasks()->create(['title' => 'Terlambat selesai', 'task_status_id' => $complete->id, 'due_date' => '2026-10-31 10:00:00']);
        $user->tasks()->create(['title' => 'Tenggat lewat', 'due_date' => '2026-10-31 10:00:00']);
        $user->tasks()->create(['title' => 'Jadwal berakhir', 'ends_at' => '2026-10-31 10:00:00']);

        $this->actingAs($user)->get(route('user.tasks.index', ['due' => 'overdue']))
            ->assertOk()
            ->assertSee('Tenggat lewat')
            ->assertSee('Jadwal berakhir')
            ->assertDontSee('Terlambat selesai');
    }

    public function test_database_seeder_adds_default_task_statuses(): void
    {
        $this->seed();

        $this->assertDatabaseHas('task_statuses', ['name' => 'Belum dimulai']);
        $this->assertDatabaseHas('task_statuses', ['name' => 'Sedang dikerjakan']);
        $this->assertDatabaseHas('task_statuses', ['name' => 'Selesai']);
    }
}
