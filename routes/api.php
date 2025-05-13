<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'DailyQuest API v1']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [UserController::class, 'show']);

    Route::get('/collections', [CollectionController::class, 'index']);
    Route::get('/collections/{id}', [CollectionController::class, 'tasks']);
    Route::post('/collections', [CollectionController::class, 'store']);
    Route::delete('/collections/{id}', [CollectionController::class, 'destroy']);

    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::post('/collections/{id}/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::put('/tasks/{id}/complete', [TaskController::class, 'complete']);
    Route::put('/tasks/{id}/uncomplete', [TaskController::class, 'uncomplete']);
    Route::delete('/task/{id}', [TaskController::class, 'destroy']);
});
