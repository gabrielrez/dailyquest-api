<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_can_invite_new_user()
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_accept_a_valid_invitation()
    {
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

        $this->assertTrue(
            $collection->users()->where('users.id', $user->id)->exists()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_accept_an_expired_invitation()
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson(route('invitations.accept', [
            'token' => $invitation->token,
        ]));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Invitation has expired',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_accept_already_accepted_invitation()
    {
        $invitation = Invitation::factory()->create(['status' => 'accepted']);

        $response = $this->postJson(route('invitations.accept', [
            'token' => $invitation->token,
        ]));

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Invitation already accepted',
            ]);
    }
}
