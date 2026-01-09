<?php

use App\Mail\InvitationMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

describe('Collection Index & Show', function () {
    it('returns paginated collections for the authenticated user', function () {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        Collection::factory()->count(3)->for($owner, 'owner')->create();

        $response = actingAs($owner)->getJson(route('collections.index'));

        $response->assertOk()->assertJsonStructure(['data', 'links']);
    });

    it('returns a single collection with loaded relations', function () {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = actingAs($owner)->getJson(route('collections.show', $collection));

        $response->assertOk()->assertJsonStructure(['data']);
    });
});

describe('Collection Creation & Update', function () {
    it('creates a new collection for the authenticated user', function () {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $response = actingAs($owner)->postJson(route('collections.store'), [
            'name' => 'Collection Name',
            'description' => 'Collection Description',
        ]);

        $response->assertCreated()->assertJsonStructure(['data']);
    });

    it('allows the owner to update their collection', function () {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create(['name' => 'Old Name']);

        $response = actingAs($owner)->putJson(route('collections.update', $collection), [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
    });

    it('forbids non-owners from updating the collection', function () {
        [$owner, $collaborator] = User::factory()->count(2)->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = actingAs($collaborator)
            ->putJson(route('collections.update', $collection), ['name' => 'Unauthorized Update']);

        $response->assertForbidden();
    });
});

describe('Collection Deletion', function () {
    it('allows the owner to delete their collection', function () {
        /** @var \App\Models\User $owner */
        $owner = User::factory()->create();

        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = actingAs($owner)->deleteJson(route('collections.destroy', $collection));

        $response->assertNoContent();
        expect(Collection::find($collection->id))->toBeNull();
    });

    it('forbids non-owners from deleting the collection', function () {
        [$owner, $collaborator] = User::factory()->count(2)->create();
        $collection = Collection::factory()->for($owner, 'owner')->create();

        $response = actingAs($collaborator)->deleteJson(route('collections.destroy', $collection));

        $response->assertJsonStructure(['message']);
        expect(Collection::find($collection->id))->not->toBeNull();
    });
});

describe('Collection Invitations', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create();
        $this->collection = Collection::factory()->for($this->owner, 'owner')->create();
    });

    it('allows the owner to invite a new user without an account', function () {
        Mail::fake();

        $response = actingAs($this->owner)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => 'newuser@example.com']
        );

        $response->assertCreated()->assertJsonStructure(['data' => ['id', 'collection_id', 'email', 'token']]);
        $this->assertDatabaseHas('invitations', [
            'collection_id' => $this->collection->id,
            'email' => 'newuser@example.com',
        ]);

        Mail::assertSent(InvitationMail::class);
    });

    it('allows the owner to invite an existing user', function () {
        Mail::fake();

        $user = User::factory()->create();

        $response = actingAs($this->owner)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => $user->email]
        );

        $response->assertCreated();
        $this->assertDatabaseHas('invitations', [
            'collection_id' => $this->collection->id,
            'email' => $user->email,
        ]);

        Mail::assertSent(InvitationMail::class);
    });

    it('forbids the owner from inviting themselves', function () {
        $response = actingAs($this->owner)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => $this->owner->email]
        );

        $response->assertForbidden()
            ->assertJson(['message' => 'You cannot invite yourself to your own collection']);
    });

    it('forbids inviting a user already in the collection', function () {
        $user = User::factory()->create();
        $this->collection->users()->attach($user->id);

        $response = actingAs($this->owner)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => $user->email]
        );

        $response->assertConflict()->assertJson(['message' => 'User already in collection']);
    });

    it('forbids inviting a user with a pending invitation', function () {
        $user = User::factory()->create();

        Invitation::factory()->create([
            'collection_id' => $this->collection->id,
            'email' => $user->email,
            'status' => 'pending',
        ]);

        $response = actingAs($this->owner)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => $user->email]
        );

        $response->assertConflict()->assertJson(['message' => 'User already has a pending invitation']);
    });

    it('forbids non-owners from inviting users', function () {
        /** @var \App\Models\User $collaborator */
        $collaborator = User::factory()->create();

        $response = actingAs($collaborator)->postJson(
            route('collections.users.invite', $this->collection),
            ['user_email' => 'someone@example.com']
        );

        $response->assertForbidden();
    });
});
