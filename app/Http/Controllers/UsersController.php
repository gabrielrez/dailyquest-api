<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
