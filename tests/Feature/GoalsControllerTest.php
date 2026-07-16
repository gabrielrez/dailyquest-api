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

    public function test_user_can_create_goal(): void
    {
        $collection = Collection::factory()->create();

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
            'owner_id' => $user->id,
            'name' => 'Goal name',
            'description' => 'Goal description',
            'status' => 'to_do',
        ]);

        $goal = Goal::where('collection_id', $collection->id)->first();
        $this->assertSame(1, $goal->order);
    }

    public function test_goal_order_increments_within_collection(): void
    {
        $collection = Collection::factory()->create();

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

        $this->assertSame(2, $goal->order);
    }

    public function test_list_returns_goals_filtered_by_collection_id(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);
        Goal::factory()->count(2)->for($collection)->create();
        Goal::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.list', [
            'collection_id' => $collection->id,
        ]));

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_list_requires_collection_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.list'));

        $response->assertStatus(400)
            ->assertJson(['message' => 'Collection ID is required']);
    }

    public function test_list_forbids_non_collaborator(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.list', [
            'collection_id' => $collection->id,
        ]));

        $response->assertForbidden()
            ->assertJson(['message' => 'You are not authorized to access this collection']);
    }

    public function test_index_returns_goals_ordered(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);
        $second = Goal::factory()->for($collection)->create(['order' => 2]);
        $first = Goal::factory()->for($collection)->create(['order' => 1]);

        $response = $this->actingAs($user)->getJson(route('collections.goals.index', $collection));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertSame([$first->id, $second->id], $ids->toArray());
    }

    public function test_index_forbids_non_collaborator(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.index', $collection));

        $response->assertForbidden();
    }

    public function test_show_returns_goal(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);
        $goal = Goal::factory()->for($collection)->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.show', [
            'collection' => $collection,
            'goal' => $goal,
        ]));

        $response->assertOk()->assertJsonPath('data.id', $goal->id);
    }

    public function test_show_returns_404_when_goal_not_in_collection(): void
    {
        $collection = Collection::factory()->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);
        $goal = Goal::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.goals.show', [
            'collection' => $collection,
            'goal' => $goal,
        ]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Goal not found in this collection']);
    }

    public function test_update_allows_collection_owner_to_update_goal(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();
        $goal = Goal::factory()->for($collection)->create(['name' => 'Old name']);

        $response = $this->actingAs($owner)->putJson(route('collections.goals.update', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'name' => 'New name',
            'status' => 'to_do',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New name');

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'name' => 'New name',
        ]);
    }

    public function test_update_forbids_non_owner_collaborator(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();
        $collection->users()->attach($collaborator);
        $goal = Goal::factory()->for($collection)->create();

        $response = $this->actingAs($collaborator)->putJson(route('collections.goals.update', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'name' => 'New name',
            'status' => 'to_do',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner of the collection can update goals']);
    }

    public function test_update_status_updates_goal_and_collection(): void
    {
        $collection = Collection::factory()->create(['status' => 'in_progress']);
        $user = User::factory()->create();
        $collection->users()->attach($user);
        $goal = Goal::factory()->for($collection)->create(['status' => 'to_do']);

        $response = $this->actingAs($user)->patchJson(route('collections.goals.update-status', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'status' => 'done',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.goal.status', 'done')
            ->assertJsonPath('data.collection.status', 'completed');

        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'status' => 'done']);
    }

    public function test_user_can_reorder_goals(): void
    {
        $collection = Collection::factory()->create();

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
            'message' => 'Goals reordered',
        ]);

        foreach (array_reverse($goals->pluck('id')->toArray()) as $index => $goalId) {
            $this->assertDatabaseHas('goals', [
                'id' => $goalId,
                'order' => $index + 1,
            ]);
        }
    }

    public function test_user_can_assign_goal_to_other_user(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        $user = User::factory()->create();
        $collection->users()->attach($user);

        $assignee = User::factory()->create();
        $collection->users()->attach($assignee);

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'user_username' => $assignee->username,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_user_can_unassign_goal(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        $user = User::factory()->create();
        $collection->users()->attach($user);

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'user_username' => null,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => null,
        ]);
    }

    public function test_user_cannot_assign_goal_to_other_user_that_does_not_belong_to_collection(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        $user = User::factory()->create();
        $collection->users()->attach($user);

        $assignee = User::factory()->create();

        $this->actingAs($user);

        $response = $this->patchJson(route('collections.goals.assign', [
            'collection' => $collection,
            'goal' => $goal,
        ]), [
            'user_username' => $assignee->username,
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'The user is not a collaborator of this collection']);
    }

    public function test_destroy_deletes_goal(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $response = $this->actingAs($user)->deleteJson(route('collections.goals.destroy', [
            'collection' => $collection,
            'goal' => $goal,
        ]));

        $response->assertOk()->assertJson(['message' => 'Goal deleted']);

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_destroy_returns_404_when_goal_not_in_collection(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->create();
        $user = User::factory()->create();
        $collection->users()->attach($user);

        $response = $this->actingAs($user)->deleteJson(route('collections.goals.destroy', [
            'collection' => $collection,
            'goal' => $goal,
        ]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Goal not found in this collection']);
    }
}
