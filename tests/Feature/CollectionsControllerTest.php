<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Mail\UserRemovedMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CollectionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_collections_for_the_user(): void
    {
        $user = User::factory()->create();
        Collection::factory()->for($user, 'owner')->create();
        $collaborating = Collection::factory()->create();
        $collaborating->users()->attach($user);
        Collection::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.index'));

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_collection_for_owner(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)->getJson(route('collections.show', $collection));

        $response->assertOk()
            ->assertJsonPath('data.id', $collection->id);
    }

    public function test_show_returns_403_for_non_collaborator(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $response = $this->actingAs($user)->getJson(route('collections.show', $collection));

        $response->assertForbidden()
            ->assertJson(['message' => 'You are not authorized to access this collection']);
    }

    public function test_store_creates_a_collection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('collections.store'), [
            'name' => 'My Collection',
            'description' => 'desc',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'My Collection')
            ->assertJsonPath('data.owner_id', $user->id);

        $this->assertDatabaseHas('collections', [
            'name' => 'My Collection',
            'owner_id' => $user->id,
        ]);
    }

    public function test_update_allows_owner_to_update_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create(['name' => 'Old name']);

        $response = $this->actingAs($user)->putJson(route('collections.update', $collection), [
            'name' => 'New name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New name');

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'New name',
        ]);
    }

    public function test_update_forbids_non_owner(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();
        $collection->users()->attach($collaborator);

        $response = $this->actingAs($collaborator)->putJson(route('collections.update', $collection), [
            'name' => 'New name',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner can update this collection']);
    }

    public function test_destroy_allows_owner_to_delete_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)->deleteJson(route('collections.destroy', $collection));

        $response->assertOk()
            ->assertJson(['message' => 'Collection deleted']);

        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    public function test_destroy_forbids_non_owner(): void
    {
        $owner = User::factory()->create();
        $non_owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($non_owner)->deleteJson(route('collections.destroy', $collection));

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner can delete this collection']);

        $this->assertDatabaseHas('collections', ['id' => $collection->id]);
    }

    public function test_owner_can_invite_a_new_user_who_does_not_have_an_account(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'collection_id' => $collection->id,
            'email' => 'newuser@example.com',
        ]);

        Mail::assertSent(InvitationMail::class);
    }

    public function test_owner_can_invite_an_existing_user_who_already_has_an_account(): void
    {
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
                'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'collection_id' => $collection->id,
            'email' => $user->email,
        ]);

        Mail::assertSent(InvitationMail::class);
    }

    public function test_owner_cannot_invite_themselves_to_their_own_collection(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => $owner->email,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You cannot invite yourself to your own collection']);
    }

    public function test_owner_cannot_invite_a_user_who_is_already_in_the_collection(): void
    {
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
    }

    public function test_owner_cannot_invite_a_user_who_has_a_pending_invitation(): void
    {
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
    }

    public function test_non_owner_cannot_invite_users_to_a_collection(): void
    {
        $owner = User::factory()->create();
        $non_owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($non_owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => 'someone@example.com',
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner can add users to this collection']);
    }

    public function test_owner_can_remove_a_user_from_the_collection(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $user = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();
        $collection->users()->attach($user);

        $response = $this->actingAs($owner)
            ->deleteJson(route('collections.users.destroy', $collection), [
                'user_email' => $user->email,
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'User removed from collection']);

        $this->assertFalse($collection->users()->where('users.id', $user->id)->exists());

        Mail::assertSent(UserRemovedMail::class);
    }

    public function test_non_owner_cannot_remove_a_user_from_the_collection(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $non_owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();
        $collection->users()->attach($user);
        $collection->users()->attach($non_owner);

        $response = $this->actingAs($non_owner)
            ->deleteJson(route('collections.users.destroy', $collection), [
                'user_email' => $user->email,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner can remove users from this collection']);
    }

    public function test_owner_cannot_be_removed_from_their_own_collection(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)
            ->deleteJson(route('collections.users.destroy', $collection), [
                'user_email' => $owner->email,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Owner cannot be removed from their own collection']);
    }
}
