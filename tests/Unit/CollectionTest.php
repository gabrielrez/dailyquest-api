<?php

namespace Tests\Unit;

use App\Http\Enums\GoalStatusEnum;
use App\Models\Collection;
use App\Models\Goal;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_belongs_to_an_owner(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        $this->assertInstanceOf(User::class, $collection->owner);
        $this->assertSame($user->id, $collection->owner->id);
    }

    public function test_collection_has_many_goals(): void
    {
        $collection = Collection::factory()->hasGoals(3)->create();

        $this->assertCount(3, $collection->goals);
        $this->assertInstanceOf(Goal::class, $collection->goals->first());
    }

    public function test_collection_has_many_invitations(): void
    {
        $collection = Collection::factory()->hasInvitations(2)->create();

        $this->assertCount(2, $collection->invitations);
        $this->assertInstanceOf(Invitation::class, $collection->invitations->first());
    }

    public function test_collection_has_many_collaborating_users(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $collection->users()->attach($user);

        $this->assertCount(1, $collection->users);
        $this->assertInstanceOf(User::class, $collection->users->first());
    }

    public function test_belongs_to_user_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user, 'owner')->create();

        $this->assertTrue($collection->belongsToUser($user));
    }

    public function test_belongs_to_user_returns_true_for_collaborator(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->users()->attach($user);

        $this->assertTrue($collection->belongsToUser($user));
    }

    public function test_belongs_to_user_returns_false_if_not_owner_or_collaborator(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $this->assertFalse($collection->belongsToUser($user));
    }

    public function test_belongs_to_user_with_owner_only_true_returns_false_for_collaborator(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->users()->attach($user);

        $this->assertFalse($collection->belongsToUser($user, owner_only: true));
    }

    public function test_scope_owned_by_filters_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owned = Collection::factory()->for($owner, 'owner')->create();
        Collection::factory()->for($other, 'owner')->create();

        $result = Collection::ownedBy($owner->id)->get();

        $this->assertCount(1, $result);
        $this->assertSame($owned->id, $result->first()->id);
    }

    public function test_scope_status_filters_by_status(): void
    {
        $in_progress = Collection::factory()->create(['status' => 'in_progress']);
        Collection::factory()->create(['status' => 'completed']);

        $result = Collection::status('in_progress')->get();

        $this->assertCount(1, $result);
        $this->assertSame($in_progress->id, $result->first()->id);
    }

    public function test_scope_for_user_returns_collections_where_user_is_owner_or_collaborator(): void
    {
        $user = User::factory()->create();
        $owned = Collection::factory()->for($user, 'owner')->create();
        $collaborator = Collection::factory()->create();
        $collaborator->users()->attach($user);

        $result = Collection::forUser($user->id)->get();

        $this->assertContains($owned->id, $result->pluck('id'));
        $this->assertContains($collaborator->id, $result->pluck('id'));
    }

    public function test_is_completed_returns_true_if_all_goals_are_done(): void
    {
        $collection = Collection::factory()->create();
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE]);

        $this->assertTrue($collection->isCompleted());
    }

    public function test_is_completed_returns_false_if_any_goal_is_not_done(): void
    {
        $collection = Collection::factory()->create();
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DOING]);

        $this->assertFalse($collection->isCompleted());
    }
}
