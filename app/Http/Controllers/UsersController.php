<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePictureRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function profile(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(UserUpdateRequest $request)
    {
        $request->validated();
        $user = $request->user();

        $user->update($request->only([
            'full_name',
            'username'
        ]));

        return new UserResource($user);
    }

    public function uploadProfilePicture(ProfilePictureRequest $request)
    {
        $user = $request->user();

        if (!$request->hasFile('profile_picture')) {
            abort(400, 'Missing profile picture');
        }

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->update(['profile_picture' => $path]);

        return response()->json([
            'data' => ['profile_picture' => $user->profile_picture_url],
        ]);
    }
}
