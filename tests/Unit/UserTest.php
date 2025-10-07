<?php

use App\Models\User;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('password', function () {
    it('is hashed', function () {
        $user = User::factory()->create(['password' => 'secret']);

        expect($user->password)->not->toBe('secret')
            ->and(password_verify('secret', $user->password))->toBeTrue();
    });
});

describe('relationships', function () {
    it('has owned collections', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $user->id]);

        $owned_collections = $user->ownedCollections;

        expect($owned_collections)->toHaveCount(1)
            ->and($owned_collections->first()->id)->toBe($collection->id)
            ->and($owned_collections->first()->owner_id)->toBe($user->id)
            ->and($owned_collections->first())->toBeInstanceOf(Collection::class);
    });

    it('has collaborative collections', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $user->collections()->attach($collection->id);

        $collections = $user->collections;

        expect($collections)->toHaveCount(1)
            ->and($collections->first()->id)->toBe($collection->id)
            ->and($collections->first()->owner_id)->not->toBe($user->id)
            ->and($collections->first())->toBeInstanceOf(Collection::class);
    });

    it('has owned goals', function () {
        $user = User::factory()->hasGoals(3)->create();

        $goals = $user->goals;

        expect($goals)->toHaveCount(3)
            ->and($goals->first()->owner_id)->toBe($user->id)
            ->and($goals->first())->toBeInstanceOf(Goal::class);
    });
});

describe('jwt identifier', function () {
    it('returns user id', function () {
        $user = User::factory()->create();

        expect($user->getJWTIdentifier())->toBe($user->id);
    });
});

describe('profile picture url', function () {
    it('returns null if no picture', function () {
        $user = User::factory()->create(['profile_picture' => null]);

        expect($user->profile_picture_url)->toBeNull();
    });


    it('returns full path when picture exists', function () {
        $user = User::factory()->create(['profile_picture' => 'avatars/me.png']);

        expect($user->profile_picture_url)->toBe(asset('storage/avatars/me.png'));
    });
});
