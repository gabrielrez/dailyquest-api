<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectionAddUserRequest;
use App\Http\Requests\CollectionCreateRequest;
use App\Http\Services\CollectionService;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionsController extends Controller
{
    protected CollectionService $service;

    public function __construct()
    {
        $this->service = new CollectionService();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $collections = $this->service->filterPaginated($request, $user);

        return $this->respond($collections);
    }

    public function show(Request $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user)) {
            return $this->failForbidden('You are not authorized to access this collection');
        }

        $collection->load(['owner', 'users', 'goals']);

        return $this->respond($collection);
    }

    public function store(CollectionCreateRequest $request)
    {
        $user = $request->user();

        $collection = DB::transaction(function () use ($request, $user) {
            $collection = Collection::create([
                ...$request->validated(),
                'owner_id' => $user->id,
            ]);

            $collection->users()->attach($user->id);

            return $collection;
        });

        return $this->respondCreated($collection);
    }

    public function update(CollectionCreateRequest $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failForbidden('Only the owner can update this collection');
        }

        $collection->update($request->validated());

        return $this->respondUpdated($collection);
    }

    public function destroy(Request $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failForbidden('Only the owner can delete this collection');
        }

        $collection->delete();

        return $this->respondDeleted($collection);
    }

    public function addUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $validated = $request->validated();
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failForbidden('Only the owner can add users to this collection');
        }

        $response = $this->service->addOrInviteUser($collection, $validated['user_email']);

        return $this->respondCreated($response);
    }

    public function removeUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $validated = $request->validated();
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failForbidden('Only the owner can remove users from this collection');
        }

        $response = $this->service->removeOrNotifyUser($collection, $validated['user_email']);

        return $this->respondDeleted($response, 200);
    }
}
