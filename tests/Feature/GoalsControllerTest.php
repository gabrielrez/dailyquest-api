<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_goal()
    {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $this->actingAs($user);

        $response = $this->postJson(route('collections.goals.store', [
            'collection' => $collection,
        ]), [
            'name' => 'Goal name',
            'description' => 'Goal description',
            'status' => 'to_do',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('goals', [
            'collection_id' => $collection->id,
            'owner_id'      => $user->id,
            'name'          => 'Goal name',
            'description'   => 'Goal description',
            'status'        => 'to_do',
        ]);

        $goal = Goal::where('collection_id', $collection->id)->first();
        $this->assertEquals(1, $goal->order);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function goal_order_increments_within_collection()
    {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        $this->actingAs($user);

        $this->postJson(route('collections.goals.store', ['collection' => $collection]), [
            'name' => 'First Goal',
            'description' => 'Desc',
            'status' => 'to_do',
        ]);

        $response = $this->postJson(route('collections.goals.store', ['collection' => $collection]), [
            'name' => 'Second Goal',
            'description' => 'Desc',
            'status' => 'to_do',
        ]);

        $response->assertCreated();

        $goal = Goal::where('collection_id', $collection->id)
            ->orderByDesc('id')
            ->first();

        $this->assertEquals(2, $goal->order);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_reorder_goals()
    {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        $goals = Goal::factory()->count(3)->create([
            'collection_id' => $collection->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user);

        $new_order = $goals->pluck('id')->reverse()->map(fn($id) => ['id' => $id])->values()->toArray();

        $response = $this->patchJson(route('collections.goals.reorder', [
            'collection' => $collection,
        ]), [
            'goals_data' => $new_order,
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'message' => 'Goals reordered',
            ],
        ]);

        foreach (array_reverse($goals->pluck('id')->toArray()) as $index => $goalId) {
            $this->assertDatabaseHas('goals', [
                'id' => $goalId,
                'order' => $index + 1,
            ]);
        }
    }

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
