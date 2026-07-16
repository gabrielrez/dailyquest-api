<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(UserRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'full_name' => $validated['full_name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
        ]);

        if (!empty($validated['token'])) {
            $invitation = Invitation::where('token', $validated['token'])->first();

            // Here we don't check if the invitation is expired, because we want as much as new users as possible.
            if ($invitation && $invitation->status === 'pending') {
                $invitation->update(['status' => 'accepted']);
                $invitation->collection->users()->attach($user->id);
            }
        }

        $token = Auth::attempt([
            'email'     => $validated['email'],
            'password'  => $validated['password'],
        ]);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(UserLoginRequest $request)
    {
        $credentials = [
            'email'    => $request->validated()['email'],
            'password' => $request->validated()['password'],
        ];

        if (!$token = Auth::attempt($credentials)) {
            abort(401, 'Invalid credentials');
        }

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
            ],
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'Logout successful']);
    }
}
