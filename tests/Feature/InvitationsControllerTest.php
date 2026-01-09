<?php

use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Invitation Acceptance', function () {
    it('allows user to accept a valid invitation', function () {
        $collection = Collection::factory()->create();

        /** @var \App\Models\User $user */
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

    it('forbids user to accept an expired invitation', function () {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson(route('invitations.accept', [
            'token' => $invitation->token,
        ]));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Invitation has expired',
            ]);
    });

    it('prevents user from accepting an already accepted invitation', function () {
        $invitation = Invitation::factory()->create(['status' => 'accepted']);

        $response = $this->postJson(route('invitations.accept', [
            'token' => $invitation->token,
        ]));

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Invitation already accepted',
            ]);
    });
});
