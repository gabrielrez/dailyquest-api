<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function show(Request $request, Task $task): JsonResponse
    {
        if ($task->collection->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($task);
    }



    public function store(Request $request)
    {
        //
    }



    public function updated(Request $request)
    {
        //
    }



    public function complete(Request $request)
    {
        //
    }



    public function uncomplete(Request $request)
    {
        //
    }



    public function destroy(Request $request)
    {
        //
    }
}
