<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
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

        return $this->respondCreated([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function login(UserLoginRequest $request)
    {
        $credentials = [
            'email'    => $request->validated()['email'],
            'password' => $request->validated()['password'],
        ];

        if (!$token = Auth::attempt($credentials)) {
            return $this->failUnauthorized('Invalid credentials');
        }

        return $this->respond([
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return $this->respond('Logout successful');
    }
}
