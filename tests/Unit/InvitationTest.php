<?php

namespace Tests\Unit;

use App\Http\Enums\InvitationStatusEnum;
use App\Models\Collection;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_belongs_to_a_collection(): void
    {
        $collection = Collection::factory()->create();
        $invitation = Invitation::factory()->for($collection)->create();

        $this->assertInstanceOf(Collection::class, $invitation->collection);
        $this->assertSame($collection->id, $invitation->collection->id);
    }

    public function test_is_expired_returns_true_when_expires_at_is_in_the_past(): void
    {
        $invitation = Invitation::factory()->create([
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($invitation->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_in_the_future(): void
    {
        $invitation = Invitation::factory()->create([
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse($invitation->isExpired());
    }

    public function test_find_pending_returns_invitation_if_pending_exists(): void
    {
        $collection = Collection::factory()->create();
        $invitation = Invitation::factory()->create([
            'collection_id' => $collection->id,
            'email' => 'test@example.com',
            'status' => InvitationStatusEnum::PENDING,
        ]);

        $found = Invitation::findPending($collection, 'test@example.com');

        $this->assertNotNull($found);
        $this->assertSame($invitation->id, $found->id);
    }

    public function test_find_pending_returns_null_if_no_pending_invitation_exists(): void
    {
        $collection = Collection::factory()->create();

        $found = Invitation::findPending($collection, 'notfound@example.com');

        $this->assertNull($found);
    }

    public function test_find_pending_does_not_return_if_invitation_is_not_pending(): void
    {
        $collection = Collection::factory()->create();
        Invitation::factory()->create([
            'collection_id' => $collection->id,
            'email' => 'test@example.com',
            'status' => InvitationStatusEnum::ACCEPTED,
        ]);

        $found = Invitation::findPending($collection, 'test@example.com');

        $this->assertNull($found);
    }
}
