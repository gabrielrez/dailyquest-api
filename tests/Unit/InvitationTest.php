<?php

use App\Http\Enums\InvitationStatusEnum;
use App\Models\Collection;
use App\Models\Invitation;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('invitation belongs to a collection', function () {
    $collection = Collection::factory()->create();
    $invitation = Invitation::factory()->for($collection)->create();

    expect($invitation->collection)->toBeInstanceOf(Collection::class)
        ->id->toBe($collection->id);
});

test('isExpired returns true when expires_at is in the past', function () {
    $invitation = Invitation::factory()->create([
        'expires_at' => Carbon::now()->subDay(),
    ]);

    expect($invitation->isExpired())->toBeTrue();
});

test('isExpired returns false when expires_at is in the future', function () {
    $invitation = Invitation::factory()->create([
        'expires_at' => Carbon::now()->addDay(),
    ]);

    expect($invitation->isExpired())->toBeFalse();
});

test('findPending returns invitation if pending exists', function () {
    $collection = Collection::factory()->create();
    $invitation = Invitation::factory()->create([
        'collection_id' => $collection->id,
        'email' => 'test@example.com',
        'status' => InvitationStatusEnum::PENDING,
    ]);

    $found = Invitation::findPending($collection, 'test@example.com');

    expect($found)->not->toBeNull()
        ->id->toBe($invitation->id);
});

test('findPending returns null if no pending invitation exists', function () {
    $collection = Collection::factory()->create();

    $found = Invitation::findPending($collection, 'notfound@example.com');

    expect($found)->toBeNull();
});

test('findPending does not return if invitation is not pending', function () {
    $collection = Collection::factory()->create();
    Invitation::factory()->create([
        'collection_id' => $collection->id,
        'email' => 'test@example.com',
        'status' => InvitationStatusEnum::ACCEPTED,
    ]);

    $found = Invitation::findPending($collection, 'test@example.com');

    expect($found)->toBeNull();
});
