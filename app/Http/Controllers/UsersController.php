<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePictureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $user['profile_picture'] = $user->profile_picture_url;

        return $this->respond($user);
    }

    public function uploadProfilePicture(ProfilePictureRequest $request)
    {
        $user = $request->user();

        if (!$request->hasFile('profile_picture')) {
            return $this->failBadRequest('Missing profile picture');
        }

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->update(['profile_picture' => $path]);

        return $this->respondUpdated([
            'profile_picture' => $user->profile_picture_url
        ]);
    }
}
