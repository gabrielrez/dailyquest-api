<?php

use App\Models\User;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('profile_picture_url returns null if no profile picture is set', function () {
    $user = User::factory()->create(['profile_picture' => null]);

    expect($user->profile_picture_url)->toBeNull();
});

test('profile_picture_url returns the correct URL when a profile picture is set', function () {
    $user = User::factory()->create([
        'profile_picture' => 'avatars/test.png',
    ]);

    expect($user->profile_picture_url)->toBe(asset('storage/avatars/test.png'));
});

test('user can have owned collections', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create(['owner_id' => $user->id]);

    expect($user->ownedCollections)->toHaveCount(1)
        ->first()->id->toBe($collection->id);
});

test('user can have collaborative collections', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create();

    $user->collections()->attach($collection->id);

    expect($user->collections)->toHaveCount(1)
        ->first()->id->toBe($collection->id);
});

test('user can have goals', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['owner_id' => $user->id]);

    expect($user->goals)->toHaveCount(1)
        ->first()->id->toBe($goal->id);
});

test('getJWTIdentifier returns the user primary key', function () {
    $user = User::factory()->create();

    expect($user->getJWTIdentifier())->toBe($user->id);
});

test('getJWTCustomClaims returns an empty array', function () {
    $user = User::factory()->create();

    expect($user->getJWTCustomClaims())->toBeArray()->toBe([]);
});
