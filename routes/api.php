<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'DailyQuest API v1'
    ]);
});

// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/collections', [CollectionController::class, 'index']); // get all collcetions
    Route::get('/collections/{id}', [CollectionController::class, 'tasks']); // get all tasks from a collection
    Route::post('/collections', [CollectionController::class, 'store']); // create a new collection
    Route::delete('/collections/{id}', [CollectionController::class, 'destroy']); // delete a collection

    Route::get('/task/{id}', [TaskController::class, 'show']); // get details of a task
    Route::post('/collections/{id}/tasks', [TaskController::class, 'store']); // create a new task for a collection
    Route::put('/task/{id}', [TaskController::class, 'update']); // edit a task
    Route::put('/task/{id}/complete', [TaskController::class, 'complete']); // complete a task
    Route::put('/task/{id}/uncomplete', [TaskController::class, 'uncomplete']); // uncomplete a task
    Route::delete('/task/{id}', [TaskController::class, 'destroy']); // delete a task
});
