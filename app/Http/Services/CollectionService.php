<?php

namespace App\Http\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Enums\InvitationStatusEnum;
use App\Mail\InvitationMail;
use App\Mail\UserRemovedMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CollectionService
{
    /**
     * Returns paginated collections filtered
     *
     * @param  Request  $request  The incoming request (filters: owner, status, per_page).
     * @param  User     $user     The authenticated user.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function filterPaginated(Request $request, User $user)
    {
        $query = Collection::with(['owner', 'users']);

        $request->boolean('owner')
            ? $query->ownedBy($user->id)
            : $query->forUser($user->id);

        if ($request->filled('status')) {
            $query->status($request->get('status'));
        }

        return $query->paginate($request->get('per_page', 10));
    }

    /**
     * Invite a user to a collection.
     *
     * @param Collection $collection
     * @param string $email
     * @return \App\Models\Invitation
     */
    public function inviteUserToCollection(Collection $collection, string $email): Invitation
    {
        $user = User::where('email', $email)->first();

        if ($user && $collection->users()->where('user_id', $user->id)->exists()) {
            throw new ConflictException('User already in collection');
        }

        $pending_invitation = Invitation::findPending($collection, $email);

        if ($pending_invitation && $pending_invitation->isExpired()) {
            throw new ConflictException('User already has a pending invitation');
        }

        $token = Str::random(40);

        $invitation = Invitation::create([
            'collection_id' => $collection->id,
            'email'         => $email,
            'token'         => $token,
        ]);

        if (!$user) {
            Mail::to($email)->send(new InvitationMail($collection, $token, is_new_user: true));

            return $invitation;
        }

        Mail::to($email)->send(new InvitationMail($collection, $token, is_new_user: false));

        return $invitation;
    }

    /**
     * Remove a user from a collection and notify them.
     *
     * @param  Collection  $collection  The collection to remove the user from.
     * @param  string      $email       The email of the user to remove.
     * @return void
     */
    public function removeAndNotifyUser(Collection $collection, string $email): void
    {
        $user_to_remove = User::where('email', $email)->first();

        if (!$user_to_remove) {
            throw new NotFoundException('User not found');
        }

        if (!$collection->belongsToUser($user_to_remove)) {
            throw new ForbiddenException('The user is not a collaborator of this collection');
        }

        if ($collection->owner_id === $user_to_remove->id) {
            throw new ForbiddenException('Owner cannot be removed from their own collection');
        }

        $collection->users()->detach($user_to_remove->id);

        Mail::to($user_to_remove->email)->send(new UserRemovedMail($collection));
    }
}
