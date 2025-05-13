<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'DailyQuest API v1'
    ]);
});
