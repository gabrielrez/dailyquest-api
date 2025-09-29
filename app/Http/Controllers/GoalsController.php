<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalAssignUserRequest;
use App\Http\Requests\GoalCreateRequest;
use App\Http\Requests\GoalReorderRequest;
use App\Http\Requests\GoalUpdateStatusRequest;
use App\Http\Services\GoalService;
use App\Models\Collection;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Http\Request;

class GoalsController extends Controller
{
    protected GoalService $service;

    public function __construct()
    {
        $this->service = new GoalService();
    }

    public function index(Request $request, Collection $collection)
    {
        if (!$collection->belongsToUser($request->user())) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        $goals = $collection
            ->goals()
            ->with('owner')
            ->orderBy('order')
            ->get();

        return $this->respond($goals);
    }

    public function show(Request $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            return $this->failNotFound('Goal not found in this collection');
        }

        return $this->respond($goal);
    }

    public function store(GoalCreateRequest $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        return $this->respondCreated(
            $this->service->create($request->validated(), $collection, $user)
        );
    }

    public function update(GoalCreateRequest $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failForbidden('Only the owner of the collection can update goals');
        }

        if ($goal->collection_id !== $collection->id) {
            return $this->failNotFound('Goal not found in this collection');
        }

        $goal->update($request->validated());

        return $this->respondUpdated($goal);
    }

    public function updateStatus(GoalUpdateStatusRequest $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            return $this->failNotFound('Goal not found in this collection');
        }

        $status = $request->validated()['status'];

        return $this->respond(
            $this->service->updateGoalAndCollectionStatus($goal, $collection, $status)
        );
    }

    public function reorder(GoalReorderRequest $request, Collection $collection)
    {
        $request->validated();

        $this->service->reorder($request->goals_data, $collection);

        return $this->respondUpdated([
            'message' => 'Goals reordered'
        ]);
    }

    public function assignTo(GoalAssignUserRequest $request, Collection $collection, Goal $goal)
    {
        $user_username = $request->validated()['user_username'] ?? null;

        if (!$collection->belongsToUser($request->user())) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        if (empty($user_username)) {
            $goal->update(['assigned_to' => null]);
            return $this->respondUpdated($goal);
        }

        return $this->respondUpdated(
            $this->service->assignTo($user_username, $goal, $collection)
        );
    }

    public function destroy(Collection $collection, Goal $goal)
    {
        if ($goal->collection_id !== $collection->id) {
            return $this->failNotFound('Goal not found in this collection');
        }

        $goal->delete();

        // Normalize the order of the goals

        if ($collection->goals()->count() === 0) {
            $collection->status = 'in_progress';
            $collection->save();
        }

        return $this->respondDeleted();
    }
}
