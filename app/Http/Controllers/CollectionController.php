<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $collections = $request->user()->collections()->withCount('tasks')->get();

        return response()->json($collections);
    }

    public function tasks(Request $request, Collection $collection): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($collection->tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required|string|max:255'
        ]);

        $collection = $request->user()->collections()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
        ]);

        return response()->json($collection, 201);
    }

    public function destroy(Request $request, Collection $collection): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }
}
