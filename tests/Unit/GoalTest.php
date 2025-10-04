<?php

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('goal belongs to a collection', function () {
    $collection = Collection::factory()->create();
    $goal = Goal::factory()->for($collection)->create();

    expect($goal->collection)->toBeInstanceOf(Collection::class)
        ->id->toBe($collection->id);
});

test('goal belongs to an owner', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user, 'owner')->create();

    expect($goal->owner)->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});
