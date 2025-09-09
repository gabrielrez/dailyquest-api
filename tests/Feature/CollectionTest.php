<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CollectionTest extends TestCase
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

        $response->assertStatus(201)
            ->assertJson(['message' => 'Invitation sent to new user']);

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
}
