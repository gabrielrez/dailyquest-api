<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalAssignTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_assign_goal_to_other_user()
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $assignee = User::factory()->create();
        $collection->users()->attach($assignee);

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal
        ]), [
            'user_username' => $assignee->username
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => $assignee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_unassign_goal()
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal
        ]), [
            'user_username' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => null
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_assign_goal_to_other_user_that_does_not_belong_to_collection()
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $assignee = User::factory()->create();

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal
        ]), [
            'user_username' => $assignee->username
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'The user is not a collaborator of this collection']);
    }
}
