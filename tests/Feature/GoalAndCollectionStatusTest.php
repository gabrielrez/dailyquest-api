<?php

use App\Http\Enums\CollectionStatusEnum;
use App\Http\Enums\GoalStatusEnum;
use App\Http\Services\GoalService;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->goalService = app()->make(GoalService::class);
});

test('marks collection as completed when all goals are done', function () {
    $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
    $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
    Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

    $status_done = GoalStatusEnum::DONE->value;
    $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

    expect($result['goal']->status)->toBe($status_done);
    expect($result['collection']->status)->toBe(CollectionStatusEnum::COMPLETED->value);
});

test('sets collection back to in progress when a goal status changes from done to doing', function () {
    $collection = Collection::factory()->create(['status' => CollectionStatusEnum::COMPLETED]);
    $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);
    Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::DONE->value]);

    $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, GoalStatusEnum::DOING->value);

    expect($result['goal']->status)->toBe(GoalStatusEnum::DOING->value);
    expect($result['collection']->status)->toBe(CollectionStatusEnum::IN_PROGRESS->value);
});

test('keeps collection in progress when not all goals are completed', function () {
    $collection = Collection::factory()->create(['status' => CollectionStatusEnum::IN_PROGRESS]);
    $goal1 = Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);
    Goal::factory()->for($collection)->create(['status' => GoalStatusEnum::TODO->value]);

    $status_done = GoalStatusEnum::DONE->value;
    $result = $this->goalService->updateGoalAndCollectionStatus($goal1, $collection, $status_done);

    expect($result['goal']->status)->toBe($status_done);
    expect($result['collection']->status)->toBe(CollectionStatusEnum::IN_PROGRESS->value);
});
