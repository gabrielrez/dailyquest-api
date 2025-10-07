<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectionAddUserRequest;
use App\Http\Requests\CollectionCreateRequest;
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

        return $this->respond($collections);
    }

    public function show(Collection $collection)
    {
        $this->authorize('collaboratorAccess', $collection);

        $collection->load([
            'owner',
            'users',
            'goals' => fn($query) => $query->orderBy('order')
        ]);

        return $this->respond($collection);
    }

    public function store(CollectionCreateRequest $request)
    {
        $collection = Collection::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        return $this->respondCreated($collection);
    }

    public function update(CollectionCreateRequest $request, Collection $collection)
    {
        $this->authorize('ownerAccess', $collection);

        $collection->update($request->validated());

        return $this->respondUpdated($collection);
    }

    public function destroy(Collection $collection)
    {
        $this->authorize('ownerAccess', $collection);

        $collection->delete();

        return $this->respondDeleted();
    }

    public function inviteUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $this->authorize('ownerAccess', $collection);

        $validated = $request->validated();
        $user = $request->user();

        if ($user->email === $validated['user_email']) {
            return $this->failForbidden('You cannot invite yourself to your own collection');
        }

        $invitation = $this->service->inviteUserToCollection($collection, $validated['user_email']);

        return $this->respondCreated($invitation);
    }

    public function removeUser(CollectionAddUserRequest $request, Collection $collection)
    {
        $this->authorize('ownerAccess', $collection);

        $validated = $request->validated();

        $this->service->removeAndNotifyUser($collection, $validated['user_email']);

        return $this->respondDeleted('User removed from collection', 200);
    }
}
