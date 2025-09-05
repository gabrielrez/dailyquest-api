<?php

namespace App\Http\Controllers;

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

        $collection = Collection::create([
            ...$request->validated(),
            'owner_id' => $user->id,
        ]);

        $collection->users()->attach($user->id);

        return $this->respondCreated($collection);
    }

    public function update(Request $request, Collection $collection)
    {
        //
    }

    public function destroy(Request $request, Collection $collection)
    {
        //
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
