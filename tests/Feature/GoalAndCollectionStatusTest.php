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

        $this->goalService = app()->make(GoalService::class);
    }

    public function test_marks_collection_as_completed_when_all_goals_are_done(): void
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

        $status_done = GoalStatusEnum::DONE->value;
        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

        $this->assertSame($status_done, $result['goal']->status);
        $this->assertSame(CollectionStatusEnum::COMPLETED->value, $result['collection']->status);
    }

    public function test_sets_collection_back_to_in_progress_when_a_goal_status_changes_from_done_to_doing(): void
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::COMPLETED]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, GoalStatusEnum::DOING->value);

        $this->assertSame(GoalStatusEnum::DOING->value, $result['goal']->status);
        $this->assertSame(CollectionStatusEnum::IN_PROGRESS->value, $result['collection']->status);
    }

    public function test_keeps_collection_in_progress_when_not_all_goals_are_completed(): void
    {
        $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
        $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
        Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);

        $status_done = GoalStatusEnum::DONE->value;
        $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

        $this->assertSame(CollectionStatusEnum::IN_PROGRESS->value, $result['collection']->status);
    }
}
