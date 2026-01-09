<?php

use App\Http\Enums\GoalStatusEnum;
use App\Models\Collection;
use App\Models\Goal;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Relationships', function () {
    it('belongs to an owner', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        expect($collection->owner)->toBeInstanceOf(User::class)
            ->id->toBe($user->id);
    });

    it('has many goals', function () {
        $collection = Collection::factory()->hasGoals(3)->create();

        expect($collection->goals)->toHaveCount(3)
            ->and($collection->goals->first())->toBeInstanceOf(Goal::class);
    });

    it('has many invitations', function () {
        $collection = Collection::factory()->hasInvitations(2)->create();

        expect($collection->invitations)->toHaveCount(2)
            ->and($collection->invitations->first())->toBeInstanceOf(Invitation::class);
    });

    it('has many collaborating users', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $collection->users()->attach($user);

        expect($collection->users)->toHaveCount(1)
            ->and($collection->users->first())->toBeInstanceOf(User::class);
    });
});

describe('belongsToUser', function () {
    test('returns true for owner', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        expect($collection->belongsToUser($user))->toBeTrue();
    });

    test('returns true for collaborator', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->users()->attach($user);

        expect($collection->belongsToUser($user))->toBeTrue();
    });

    test('returns false if not owner or collaborator', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        expect($collection->belongsToUser($user))->toBeFalse();
    });

    test('returns false for collaborator when owner_only = true', function () {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->users()->attach($user);

        expect($collection->belongsToUser($user, owner_only: true))->toBeFalse();
    });
});

describe('Scopes', function () {
    test('scopeOwnedBy filters by owner', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owned = Collection::factory()->for($owner, 'owner')->create();
        Collection::factory()->for($other, 'owner')->create();

        $result = Collection::ownedBy($owner->id)->get();

        expect($result)->toHaveCount(1)
            ->first()->id->toBe($owned->id);
    });

    test('scopeStatus filters by status', function () {
        $in_progress = Collection::factory()->create(['status' => 'in_progress']);
        Collection::factory()->create(['status' => 'completed']);

        $result = Collection::status('in_progress')->get();

        expect($result)->toHaveCount(1)
            ->first()->id->toBe($in_progress->id);
    });

    test('scopeForUser returns collections where user is owner or collaborator', function () {
        $user = User::factory()->create();
        $owned = Collection::factory()->for($user, 'owner')->create();
        $collaborator = Collection::factory()->create();
        $collaborator->users()->attach($user);

        $result = Collection::forUser($user->id)->get();

        expect($result->pluck('id'))->toContain($owned->id, $collaborator->id);
    });
});

describe('isCompleted', function () {
    it('returns true if all goals are DONE', function () {
        $collection = Collection::factory()->create();
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE]);

        expect($collection->isCompleted())->toBeTrue();
    });

    it('returns false if any goal is not DONE', function () {
        $collection = Collection::factory()->create();
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DOING]);

        expect($collection->isCompleted())->toBeFalse();
    });
});
