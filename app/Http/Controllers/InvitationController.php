<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvitationAcceptRequest;
use App\Models\Invitation;
use App\Models\User;

class InvitationController extends Controller
{
    public function accept(InvitationAcceptRequest $request)
    {
        $invitation = Invitation::where('token', $request->validated()['token'])->firstOrFail();

        if ($invitation->isExpired()) {
            abort(403, 'Invitation has expired');
        }

        if ($invitation->status === 'accepted') {
            abort(409, 'Invitation already accepted');
        }

        $invitation->update(['status' => 'accepted']);

        $user = User::where('email', $invitation->email)->firstOrFail();
        $invitation->collection->users()->attach($user->id);

        return response()->json(['message' => 'Invitation accepted']);
    }
}
