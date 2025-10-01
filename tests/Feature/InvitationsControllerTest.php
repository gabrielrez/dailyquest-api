<?php

use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('user can accept a valid invitation', function () {
    $collection = Collection::factory()->create();
    $user = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'collection_id' => $collection->id,
        'email' => $user->email,
        'token' => 'valid-token',
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('invitations.accept', [
        'token' => 'valid-token',
    ]));

    $response->assertOk()
        ->assertJson([
            'message' => 'Invitation accepted',
        ]);

    $this->assertDatabaseHas('invitations', [
        'id' => $invitation->id,
        'status' => 'accepted',
    ]);

    expect($collection->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

test('cannot accept an expired invitation', function () {
    $invitation = Invitation::factory()->expired()->create();

    $response = $this->postJson(route('invitations.accept', [
        'token' => $invitation->token,
    ]));

    $response->assertForbidden()
        ->assertJson([
            'message' => 'Invitation has expired',
        ]);
});

test('cannot accept already accepted invitation', function () {
    $invitation = Invitation::factory()->create(['status' => 'accepted']);

    $response = $this->postJson(route('invitations.accept', [
        'token' => $invitation->token,
    ]));

    $response->assertStatus(409)
        ->assertJson([
            'message' => 'Invitation already accepted',
        ]);
});
