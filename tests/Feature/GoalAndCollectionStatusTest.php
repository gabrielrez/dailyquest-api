<?php

namespace Tests\Feature;

use App\Http\Enums\CollectionStatusEnum;
use App\Http\Enums\GoalStatusEnum;
use App\Http\Services\GoalService;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalAndCollectionStatusTest extends TestCase
{
    use RefreshDatabase;

    private GoalService $goalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->goalService = app(GoalService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updates_goal_status_and_sets_collection_to_completed()
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
        $goal2 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

        $status_done = GoalStatusEnum::DONE->value;
        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

        $this->assertEquals($status_done, $result['goal']->status);
        $this->assertEquals(CollectionStatusEnum::COMPLETED->value, $result['collection']->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updates_goal_status_and_sets_collection_to_in_progress()
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::COMPLETED]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);
        $goal2 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, GoalStatusEnum::DOING->value);

        $this->assertEquals(GoalStatusEnum::DOING->value, $result['goal']->status);
        $this->assertEquals(CollectionStatusEnum::IN_PROGRESS->value, $result['collection']->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updates_goal_status_and_keeps_collection_in_progress()
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
        $goal2 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);

        $status_done = GoalStatusEnum::DONE->value;
        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

        $this->assertEquals($status_done, $result['goal']->status);
        $this->assertEquals(CollectionStatusEnum::IN_PROGRESS->value, $result['collection']->status);
    }
}
