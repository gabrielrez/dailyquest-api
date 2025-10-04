<?php

use App\Models\User;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('password is hashed', function () {
    $user = User::factory()->create(['password' => 'secret']);

    expect($user->password)->not->toBe('secret')
        ->and(password_verify('secret', $user->password))->toBeTrue();
});

test('user can have owned collections', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create(['owner_id' => $user->id]);

    $owned_collections = $user->ownedCollections;

    expect($owned_collections)->toHaveCount(1)
        ->and($owned_collections->first()->id)->toBe($collection->id)
        ->and($owned_collections->first()->owner_id)->toBe($user->id)
        ->and($owned_collections->first())->toBeInstanceOf(Collection::class);
});

test('user can have collaborative collections', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create();

    $user->collections()->attach($collection->id);

    $collections = $user->collections;

    expect($collections)->toHaveCount(1)
        ->and($collections->first()->id)->toBe($collection->id)
        ->and($collections->first()->owner_id)->not->toBe($user->id)
        ->and($collections->first())->toBeInstanceOf(Collection::class);
});

test('user can have owned goals', function () {
    $user = User::factory()->hasGoals(3)->create();

    $goals = $user->goals;

    expect($goals)->toHaveCount(3)
        ->and($goals->first()->owner_id)->toBe($user->id)
        ->and($goals->first())->toBeInstanceOf(Goal::class);
});

test('jwt identifier returns user id', function () {
    $user = User::factory()->create();

    expect($user->getJWTIdentifier())->toBe($user->id);
});

test('profile picture url returns null if no picture', function () {
    $user = User::factory()->create(['profile_picture' => null]);

    expect($user->profile_picture_url)->toBeNull();
});


test('profile picture url returns full path when picture exists', function () {
    $user = User::factory()->create(['profile_picture' => 'avatars/me.png']);

    expect($user->profile_picture_url)->toBe(asset('storage/avatars/me.png'));
});
