<?php

namespace App\Http\Services;

use App\Http\Enums\CollectionStatusEnum;
use App\Http\Enums\GoalStatusEnum;
use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoalService
{
    /**
     * Returns paginated goals filtered
     *
     * @param  Request  $request  The incoming request (filters: status, per_page).
     * @param  User     $user     The authenticated user.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function filterPaginated(Request $request, Goal $goal)
    {
        //
    }

    /**
     * Updates the status of a goal and, based on the goals of the collection,
     * updates the collection status atomically within a transaction.
     *
     * @param  Goal        $goal        The goal to be updated.
     * @param  Collection  $collection  The parent collection of the goal.
     * @param  string      $status      The new status for the goal.
     * @return array{
     *     goal: Goal,
     *     collection: Collection
     * }
     *
     * @throws \Throwable If the transaction fails.
     */
    public function updateGoalAndCollectionStatus(Goal $goal, Collection $collection, string $status)
    {
        return DB::transaction(function () use ($goal, $collection, $status) {
            $goal->update(['done_at' => null]);

            $goal->update([
                'status' => $status,
            ]);

            if ($status === GoalStatusEnum::DONE->value) {
                $goal->update(['done_at' => Carbon::now()]);
            }

            $collection_status = $collection->isCompleted()
                ? CollectionStatusEnum::COMPLETED
                : CollectionStatusEnum::IN_PROGRESS;

            $collection->update(['completed_at' => null]);

            if ($collection->status !== $collection_status) {
                $collection->update(['status' => $collection_status]);
            }

            if ($collection->isCompleted()) {
                $collection->update(['completed_at' => Carbon::now()]);
            }

            return [
                'goal' => $goal->fresh(),
                'collection' => $collection->fresh(),
            ];
        });
    }

    public function assignTo(string $username, Goal $goal, Collection $collection): Goal
    {
        $user_to_assign = User::where('username', $username)->first();

        if (!$collection->belongsToUser($user_to_assign)) {
            abort(403, 'The user is not a collaborator of this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            abort(404, 'Goal not found in this collection');
        }

        $goal->update(['assigned_to' => $user_to_assign->id]);

        return $goal;
    }

    public function create(array $data, Collection $collection, User $user): Goal
    {
        $max_order = Goal::where('collection_id', $collection->id)->max('order') ?? 0;

        return Goal::create([
            ...$data,
            'collection_id' => $collection->id,
            'owner_id' => $user->id,
            'order' => $max_order + 1,
        ]);
    }

    public function reorder(array $goals_data, Collection $collection): void
    {
        DB::transaction(function () use ($goals_data, $collection) {
            foreach ($goals_data as $index => $goal_data) {
                Goal::where('id', $goal_data['id'])
                    ->where('collection_id', $collection->id)
                    ->update(['order' => $index + 1]);
            }
        });
    }
}
