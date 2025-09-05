<?php

namespace App\Http\Controllers;

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
            return $this->failUnauthorized('You are not authorized to access this collection');
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
            return $this->failUnauthorized('You are not authorized to access this collection');
        }

        $collection->update($request->validated());

        return $this->respondUpdated($collection);
    }

    public function destroy(Request $request, Collection $collection)
    {
        $user = $request->user();

        if (!$collection->belongsToUser($user, owner_only: true)) {
            return $this->failUnauthorized('You are not authorized to access this collection');
        }

        $collection->delete();

        return $this->respondDeleted($collection);
    }

    public function listUsers(Request $request, Collection $collection)
    {
        //
    }

    public function addUser(Request $request, Collection $collection)
    {
        //
    }

    public function removeUser(Request $request, Collection $collection)
    {
        //
    }
}
