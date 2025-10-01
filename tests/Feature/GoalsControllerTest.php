<?php

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('user can create goal', function () {
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
    expect($goal->order)->toBe(1);
});

test('goal order increments within collection', function () {
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

    expect($goal->order)->toBe(2);
});

test('user can reorder goals', function () {
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
});

test('user can assign goal to other user', function () {
    $collection = Collection::factory()->create();
    $goal = Goal::factory()->for($collection)->create();

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

    $response->assertOk();
    $this->assertDatabaseHas('goals', [
        'id' => $goal->id,
        'assigned_to' => $assignee->id
    ]);
});

test('user can unassign goal', function () {
    $collection = Collection::factory()->create();
    $goal = Goal::factory()->for($collection)->create();

    $user = User::factory()->create();
    $collection->users()->attach($user);

    $this->actingAs($user);

    $response = $this->patchJson(route('collections.goals.assign', [
        'collection' => $collection,
        'goal' => $goal
    ]), [
        'user_username' => null
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('goals', [
        'id' => $goal->id,
        'assigned_to' => null
    ]);
});

test('user cannot assign goal to other user that does not belong to collection', function () {
    $collection = Collection::factory()->create();
    $goal = Goal::factory()->for($collection)->create();

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
});
