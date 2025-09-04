<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Http\Request;

class GoalsController extends Controller
{
    public function index(Request $request){
        //
    }

    public function show(Request $request, Collection $collection, Goal $goal){
        //
    }

    public function store(Request $request, Collection $collection){
        //
    }

    public function update(Request $request, Collection $collection, Goal $goal){
        //
    }

    public function toggleComplete(Request $request, Collection $collection, Goal $goal){
        //
    }

    public function destroy(Request $request, Collection $collection, Goal $goal){
        //
    }
}
