<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalAssignUserRequest;
use App\Http\Requests\GoalCreateRequest;
use App\Http\Requests\GoalReorderRequest;
use App\Http\Requests\GoalUpdateStatusRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\GoalResource;
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

    public function list(Request $request)
    {
        $user = $request->user();

        if (!$collection_id = $request->collection_id) {
            abort(400, 'Collection ID is required');
        }

        $collection = Collection::where('id', $collection_id)->firstOrFail();

        if (!$collection->belongsToUser($user)) {
            abort(403, 'You are not authorized to access this collection');
        }

        return GoalResource::collection($collection->goals);
    }

    public function index(Request $request, Collection $collection)
    {
        if (!$collection->belongsToUser($request->user())) {
            abort(403, 'You are not authorized to access this collection');
        }

        $goals = $collection
            ->goals()
            ->with('owner')
            ->orderBy('order')
            ->get();

        return GoalResource::collection($goals);
    }

    public function show(Request $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            abort(403, 'You are not authorized to access this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            abort(404, 'Goal not found in this collection');
        }

        return new GoalResource($goal);
    }

    public function store(GoalCreateRequest $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            abort(403, 'You are not authorized to access this collection');
        }

        $goal = $this->service->create($request->validated(), $collection, $user);

        return (new GoalResource($goal))->response()->setStatusCode(201);
    }

    public function update(GoalCreateRequest $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            abort(403, 'Only the owner of the collection can update goals');
        }

        if ($goal->collection_id !== $collection->id) {
            abort(404, 'Goal not found in this collection');
        }

        $goal->update($request->validated());

        return new GoalResource($goal);
    }

    public function updateStatus(GoalUpdateStatusRequest $request, Collection $collection, Goal $goal)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            abort(403, 'You are not authorized to access this collection');
        }

        if ($goal->collection_id !== $collection->id) {
            abort(404, 'Goal not found in this collection');
        }

        $status = $request->validated()['status'];

        $result = $this->service->updateGoalAndCollectionStatus($goal, $collection, $status);

        return response()->json([
            'data' => [
                'goal' => new GoalResource($result['goal']),
                'collection' => new CollectionResource($result['collection']),
            ],
        ]);
    }

    public function reorder(GoalReorderRequest $request, Collection $collection)
    {
        $request->validated();

        $this->service->reorder($request->goals_data, $collection);

        return response()->json(['message' => 'Goals reordered']);
    }

    public function assignTo(GoalAssignUserRequest $request, Collection $collection, Goal $goal)
    {
        $user_username = $request->validated()['user_username'] ?? null;

        if (!$collection->belongsToUser($request->user())) {
            abort(403, 'You are not authorized to access this collection');
        }

        if (empty($user_username)) {
            $goal->update(['assigned_to' => null]);
            return new GoalResource($goal);
        }

        $goal = $this->service->assignTo($user_username, $goal, $collection);

        return new GoalResource($goal);
    }

    public function destroy(Collection $collection, Goal $goal)
    {
        if ($goal->collection_id !== $collection->id) {
            abort(404, 'Goal not found in this collection');
        }

        $goal->delete();

        // Normalize the order of the goals

        if ($collection->goals()->count() === 0) {
            $collection->status = 'in_progress';
            $collection->save();
        }

        return response()->json(['message' => 'Goal deleted']);
    }
}
