<?php

namespace Tests\Feature;

use App\Enums\HabitFrequency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_habits_are_visible_in_their_lifecycle_tabs(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $active = $user->habits()->create([
            'title' => 'Kebiasaan aktif',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
        ]);
        $upcoming = $user->habits()->create([
            'title' => 'Kebiasaan akan datang',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-10',
        ]);
        $ended = $user->habits()->create([
            'title' => 'Kebiasaan berakhir',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
        ]);
        $archived = $user->habits()->create([
            'title' => 'Kebiasaan arsip',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
            'archived_at' => '2026-10-25 00:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.habits.index'))
            ->assertOk()
            ->assertViewHas('habits', fn ($habits) => $habits->pluck('id')->all() === [$active->id])
            ->assertSee(route('user.habits.check-in', $active), false)
            ->assertDontSee(route('user.habits.check-in', $upcoming), false);

        foreach ([
            'upcoming' => $upcoming,
            'ended' => $ended,
            'archived' => $archived,
        ] as $status => $habit) {
            $this->get(route('user.habits.index', ['status' => $status]))
                ->assertOk()
                ->assertViewHas('habits', fn ($habits) => $habits->pluck('id')->all() === [$habit->id])
                ->assertSee(route('user.habits.edit', $habit), false)
                ->assertDontSee(route('user.habits.check-in', $habit), false);
        }
    }

    public function test_check_ins_are_rejected_for_habits_outside_the_active_lifecycle(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $upcoming = $user->habits()->create([
            'title' => 'Belum mulai',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-10',
        ]);
        $ended = $user->habits()->create([
            'title' => 'Sudah berakhir',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
        ]);
        $archived = $user->habits()->create([
            'title' => 'Sudah diarsipkan',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
            'archived_at' => '2026-10-25 00:00:00',
        ]);

        foreach ([
            [$upcoming, '2026-11-01'],
            [$ended, '2026-10-30'],
            [$archived, '2026-11-01'],
        ] as [$habit, $date]) {
            $this->actingAs($user)->post(route('user.habits.check-in', $habit), [
                'status' => 'done',
                'log_date' => $date,
            ])->assertUnprocessable();
        }

        $this->assertDatabaseCount('habit_logs', 0);
    }
}
