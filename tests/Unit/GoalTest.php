<?php

namespace Tests\Unit;

use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_belongs_to_a_collection(): void
    {
        $collection = Collection::factory()->create();
        $goal = Goal::factory()->for($collection)->create();

        $this->assertInstanceOf(Collection::class, $goal->collection);
        $this->assertSame($collection->id, $goal->collection->id);
    }

    public function test_goal_belongs_to_an_owner(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user, 'owner')->create();

        $this->assertInstanceOf(User::class, $goal->owner);
        $this->assertSame($user->id, $goal->owner->id);
    }

    public function test_done_at_is_cast_to_datetime(): void
    {
        $goal = Goal::factory()->create(['done_at' => '2026-01-01 10:00:00']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $goal->done_at);
    }

    public function test_hidden_attributes_are_not_serialized(): void
    {
        $goal = Goal::factory()->create();

        $array = $goal->toArray();

        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }
}
