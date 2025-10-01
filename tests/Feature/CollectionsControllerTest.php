<?php

use App\Mail\InvitationMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('owner can invite a new user who does not have an account', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => 'newuser@example.com',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at']
        ]);

    $this->assertDatabaseHas('invitations', [
        'collection_id' => $collection->id,
        'email' => 'newuser@example.com',
    ]);

    Mail::assertSent(InvitationMail::class);
});

test('owner can invite an existing user who already has an account', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $user = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => $user->email,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at']
        ]);

    $this->assertDatabaseHas('invitations', [
        'collection_id' => $collection->id,
        'email' => $user->email,
    ]);

    Mail::assertSent(InvitationMail::class);
});

test('owner cannot invite themselves to their own collection', function () {
    $owner = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => $owner->email,
        ]);

    $response->assertForbidden()
        ->assertJson(['message' => 'You cannot invite yourself to your own collection']);
});

test('owner cannot invite a user who is already in the collection', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();
    $collection->users()->attach($user->id);

    $response = $this->actingAs($owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => $user->email,
        ]);

    $response->assertConflict()
        ->assertJson(['message' => 'User already in collection']);
});

test('owner cannot invite a user who has a pending invitation', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();

    Invitation::factory()->create([
        'collection_id' => $collection->id,
        'email' => $user->email,
        'token' => 'valid-token',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => $user->email,
        ]);

    $response->assertConflict()
        ->assertJson(['message' => 'User already has a pending invitation']);
});

test('non-owner cannot invite users to a collection', function () {
    $owner = User::factory()->create();
    $non_owner = User::factory()->create();
    $collection = Collection::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($non_owner)
        ->postJson(route('collections.users.invite', $collection), [
            'user_email' => 'someone@example.com',
        ]);

    $response->assertForbidden()
        ->assertJson(['message' => 'Only the owner can add users to this collection']);
});
