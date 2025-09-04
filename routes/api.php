<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Try to keep a group's routes in alphabetical order

Route::get('/', fn() => response()->json([
    'message' => 'Hello World, DailyQuest API!'
]));

Route::post('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('register', [AuthController::class, 'register'])->name('auth.register');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Collections (resource)
    Route::get('collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::get('collections/{collection}', [CollectionsController::class, 'show'])->name('collections.show');
    Route::post('collections', [CollectionsController::class, 'store'])->name('collections.store');
    Route::patch('collections/{collection}', [CollectionsController::class, 'update'])->name('collections.update');
    Route::delete('collections/{collection}', [CollectionsController::class, 'destroy'])->name('collections.destroy');

    // Users inside a collection (sub-resource)
    Route::get('collections/{collection}/users', [CollectionsController::class, 'listUsers'])->name('collections.users.index');
    Route::post('collections/{collection}/users', [CollectionsController::class, 'addUser'])->name('collections.users.store');
    Route::delete('/collections/{collection}/users/{user}', [CollectionsController::class, 'removeUser'])->name('collections.users.destroy');

    // Goals inside a collection (sub-resource)
    Route::get('collections/{collection}/goals', [GoalsController::class, 'index'])->name('collections.goals.index');
    Route::get('collections/{collection}/goals/{goal}', [GoalsController::class, 'show'])->name('collections.goals.show');
    Route::post('collections/{collection}/goals', [GoalsController::class, 'store'])->name('collections.goals.store');
    Route::patch('collections/{collection}/goals/{goal}', [GoalsController::class, 'update'])->name('collections.goals.update');
    Route::delete('collections/{collection}/goals/{goal}', [GoalsController::class, 'destroy'])->name('collections.goals.destroy');

    // Users (resource)
    Route::get('users/me', [UsersController::class, 'profile'])->name('users.profile');
});
