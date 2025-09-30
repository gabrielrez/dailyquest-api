<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationsControllerTest extends TestCase
{
    use RefreshDatabase;

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
