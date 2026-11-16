<?php

namespace Tests\Feature;

use App\Enums\GoalDirection;
use App\Enums\GoalStatus;
use App\Models\User;
use App\Services\GoalProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalDirectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_decreasing_goal_accepts_signed_updates_and_only_allows_undo_of_the_latest(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->actingAs($user)->post(route('user.goals.store'), [
            'title' => 'Berat badan',
            'type' => 'number',
            'direction' => 'decrease',
            'target_value' => 70,
            'start_value' => 80,
            'unit' => 'kg',
            'due_on' => '2026-11-30',
        ])->assertRedirect();
        $goal = $user->goals()->firstOrFail();

        $this->assertSame(GoalDirection::Decrease, $goal->direction);
        $this->assertSame(0, app(GoalProgress::class)->percent($goal));
        $this->assertSame(10, app(GoalProgress::class)->remaining($goal, 80));

        foreach ([-3, -3, 1] as $amount) {
            $this->post(route('user.goals.updates.store', $goal), [
                'amount' => $amount,
                'recorded_on' => '2026-11-01',
            ])->assertRedirect();
        }

        $this->assertSame(75, app(GoalProgress::class)->current($goal->fresh()));
        $this->assertSame(50, app(GoalProgress::class)->percent($goal->fresh()));
        $this->get(route('user.goals.index'))
            ->assertOk()
            ->assertSee('Menurunkan')
            ->assertSee('Riwayat progres')
            ->assertSee('Batalkan progres');
        $this->post(route('user.goals.updates.store', $goal), [
            'amount' => -6,
            'recorded_on' => '2026-11-01',
        ])->assertUnprocessable();
        $this->post(route('user.goals.updates.store', $goal), [
            'amount' => 0,
            'recorded_on' => '2026-11-01',
        ])->assertSessionHasErrors('amount');

        $updates = $goal->updates()->orderBy('id')->get();
        $this->delete(route('user.goals.updates.destroy', [$goal, $updates[0]]))->assertUnprocessable();
        $this->delete(route('user.goals.updates.destroy', [$goal, $updates[2]]))->assertRedirect();
        $this->assertSame(74, app(GoalProgress::class)->current($goal->fresh()));
        $this->assertSame(60, app(GoalProgress::class)->percent($goal->fresh()));
        $this->assertDatabaseMissing('goal_updates', ['id' => $updates[2]->id]);
    }

    public function test_undoing_an_achieved_goal_reopens_it_and_direction_bounds_are_validated(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->actingAs($user)->post(route('user.goals.store'), [
            'title' => 'Turunkan berat',
            'type' => 'number',
            'direction' => 'decrease',
            'target_value' => 70,
            'start_value' => 71,
        ])->assertRedirect();
        $goal = $user->goals()->firstOrFail();

        $this->post(route('user.goals.updates.store', $goal), [
            'amount' => -1,
            'recorded_on' => '2026-11-01',
        ])->assertRedirect();
        $this->patch(route('user.goals.achieve', $goal))->assertRedirect();
        $this->assertSame(GoalStatus::Achieved, $goal->fresh()->status);
        $update = $goal->updates()->firstOrFail();
        $this->delete(route('user.goals.updates.destroy', [$goal, $update]))->assertRedirect();
        $this->assertSame(GoalStatus::Active, $goal->fresh()->status);
        $this->assertSame(0, app(GoalProgress::class)->percent($goal->fresh()));

        $this->post(route('user.goals.store'), [
            'title' => 'Arah meningkat tidak valid',
            'type' => 'number',
            'direction' => 'increase',
            'target_value' => 70,
            'start_value' => 80,
        ])->assertSessionHasErrors('start_value');
        $this->post(route('user.goals.store'), [
            'title' => 'Arah menurun tidak valid',
            'type' => 'number',
            'direction' => 'decrease',
            'target_value' => 70,
            'start_value' => 60,
        ])->assertSessionHasErrors('start_value');
    }

    public function test_goal_update_undo_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $goal = $owner->goals()->create([
            'title' => 'Target milik pribadi',
            'type' => 'number',
            'direction' => 'increase',
            'target_value' => 10,
        ]);
        $update = $owner->goalUpdates()->create([
            'goal_id' => $goal->id,
            'amount' => 1,
            'recorded_on' => '2026-11-01',
        ]);

        $this->actingAs($other)
            ->delete(route('user.goals.updates.destroy', [$goal, $update]))
            ->assertForbidden();
        $this->assertDatabaseHas('goal_updates', ['id' => $update->id]);
    }
}
