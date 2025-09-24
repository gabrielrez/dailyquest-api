<?php

namespace App\Http\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Enums\CollectionStatusEnum;
use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
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
            $goal->update([
                'status' => $status,
            ]);

            $collection_status = $collection->isCompleted()
                ? CollectionStatusEnum::COMPLETED
                : CollectionStatusEnum::IN_PROGRESS;

            if ($collection->status !== $collection_status) {
                $collection->update(['status' => $collection_status]);
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
            throw new ForbiddenException('The user is not a collaborator of this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            throw new NotFoundException('Goal not found in this collection');
        }

        $goal->update(['assigned_to' => $user_to_assign->id]);

        return $goal;
    }
}
