<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectionAddUserRequest;
use App\Http\Requests\CollectionCreateRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\InvitationResource;
use App\Http\Services\CollectionService;
use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionsController extends Controller
{
    protected CollectionService $service;

    public function __construct()
    {
        $this->service = new CollectionService();
    }

    public function index(Request $request)
    {
        $collections = $this->service->filterPaginated($request, $request->user());

        return CollectionResource::collection($collections);
    }

    public function show(Request $request, Collection $collection)
    {
        if (!$collection->belongsToUser($request->user())) {
            abort(403, 'You are not authorized to access this collection');
        }

        $collection->load([
            'owner',
            'users',
            'goals' => fn($query) => $query->orderBy('order')
        ]);

        return new CollectionResource($collection);
    }

    public function store(CollectionCreateRequest $request)
    {
        $collection = Collection::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        return (new CollectionResource($collection))->response()->setStatusCode(201);
    }

    public function update(CollectionCreateRequest $request, Collection $collection)
    {
        if (!$collection->belongsToUser($request->user(), owner_only: true)) {
            abort(403, 'Only the owner can update this collection');
        }

        $collection->update($request->validated());

        return new CollectionResource($collection);
    }

    public function destroy(Request $request, Collection $collection)
    {
        if (!$collection->belongsToUser($request->user(), owner_only: true)) {
            abort(403, 'Only the owner can delete this collection');
        }

        $collection->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    public function inviteUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $validated = $request->validated();
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            abort(403, 'Only the owner can add users to this collection');
        }

        if ($user->email === $validated['user_email']) {
            abort(403, 'You cannot invite yourself to your own collection');
        }

        $invitation = $this->service->inviteUserToCollection($collection, $validated['user_email']);

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }

    public function removeUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $validated = $request->validated();

        if (!$collection->belongsToUser($request->user(), owner_only: true)) {
            abort(403, 'Only the owner can remove users from this collection');
        }

        $this->service->removeAndNotifyUser($collection, $validated['user_email']);

        return response()->json(['message' => 'User removed from collection']);
    }
}
