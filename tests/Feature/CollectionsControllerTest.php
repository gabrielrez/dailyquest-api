<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;


class CollectionsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_can_invite_user_that_does_not_has_an_account()
    {
        Mail::fake();

        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201)->assertJsonStructure([
            'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at',]
        ]);

        $this->assertDatabaseHas('invitations', [
            'collection_id' => $collection->id,
            'email' => 'newuser@example.com',
        ]);

        Mail::assertSent(InvitationMail::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_can_invite_user_that_has_an_account()
    {
        Mail::fake();

        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $user = User::factory()->create();

        $response = $this->actingAs($owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => $user->email,
            ]);

        $response->assertStatus(201)->assertJsonStructure([
            'data' => ['id', 'collection_id', 'email', 'token', 'created_at', 'updated_at',]
        ]);

        $this->assertDatabaseHas('invitations', [
            'collection_id' => $collection->id,
            'email' => $user->email,
        ]);

        Mail::assertSent(InvitationMail::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_cannot_invite_himself()
    {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => $owner->email,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You cannot invite yourself to your own collection']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_cannot_invite_users_that_are_already_in_collection()
    {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        /** @var \App\Models\User $user */
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_cannot_invite_users_that_has_a_pending_invitation()
    {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        /** @var \App\Models\User $user */
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_owner_cannot_invite_users()
    {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        /** @var \App\Models\User $non_owner */
        $non_owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = $this->actingAs($non_owner)
            ->postJson(route('collections.users.invite', $collection), [
                'user_email' => 'someone@example.com',
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only the owner can add users to this collection']);
    }
}
