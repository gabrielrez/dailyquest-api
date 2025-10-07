<?php

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

describe('Goal Creation & Reordering', function () {
    it('allows a user to create a goal', function () {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        $response = actingAs($user)->postJson(
            route('collections.goals.store', [
                'collection' => $collection,
            ]),
            ['name' => 'Goal name']
        );

        $response->assertCreated();

        $this->assertDatabaseHas('goals', [
            'collection_id' => $collection->id,
            'owner_id'      => $user->id,
            'name'          => 'Goal name'
        ]);

        $goal = Goal::where('collection_id', $collection->id)->first();
        expect($goal->order)->toBe(1);
    });

    it('ensures goal order increments as user creates goals', function () {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        actingAs($user)->postJson(
            route('collections.goals.store', [
                'collection' => $collection
            ]),
            ['name' => 'First Goal']
        );

        actingAs($user)->postJson(
            route('collections.goals.store', [
                'collection' => $collection
            ]),
            ['name' => 'Second Goal']
        );

        $goal = Goal::where('collection_id', $collection->id)
            ->orderByDesc('id')
            ->first();

        expect($goal->order)->toBe(2);
    });

    it('allows user to reorder goals within a collection', function () {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        $goals = Goal::factory()->count(3)->create([
            'collection_id' => $collection->id,
            'owner_id' => $user->id,
        ]);

        $new_order = $goals->pluck('id')->reverse()->map(fn($id) => ['id' => $id])->values()->toArray();

        $response = actingAs($user)->patchJson(
            route('collections.goals.reorder', [
                'collection' => $collection,
            ]),
            ['goals_data' => $new_order]
        );

        $response->assertOk()->assertJsonStructure(['data']);

        foreach (array_reverse($goals->pluck('id')->toArray()) as $index => $goalId) {
            $this->assertDatabaseHas('goals', [
                'id' => $goalId,
                'order' => $index + 1,
            ]);
        }
    });
});

describe('Goal Assignment', function () {
    it('allows user to assign goal to other user', function () {
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

        $response->assertOk();
        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => $assignee->id
        ]);
    });

    it('allows user to unassign goal', function () {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        $response = actingAs($user)->patchJson(
            route('collections.goals.assign', [
                'collection' => $collection,
                'goal' => $goal
            ]),
            ['user_username' => null]
        );

        $response->assertOk();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'assigned_to' => null
        ]);
    });

    it('forbids user to assign goal to other user that does not belong to collection', function () {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $collection->users()->attach($user);

        /** @var \App\Models\User $assignee */
        $assignee = User::factory()->create();

        $response = actingAs($user)->patchJson(
            route('collections.goals.assign', [
                'collection' => $collection,
                'goal' => $goal
            ]),
            ['user_username' => $assignee->username]
        );

        $response->assertForbidden();
    });
});
