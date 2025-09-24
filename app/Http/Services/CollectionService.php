<?php

namespace App\Http\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Mail\InvitationMail;
use App\Mail\UserRemovedMail;
use App\Models\Collection;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user_to_invite = User::where('email', $email)->first();

        $this->ensureNotAlreadyInCollection($collection, $email);
        $this->ensureNoPendingInvitation($collection, $email);

        return DB::transaction(function () use ($collection, $email, $user_to_invite) {
            $token = $this->generateToken();
            $invitation = $this->createInvitation($collection, $email, $token);

            $this->sendInvitationMail($email, $collection, $token, is_new_user: !$user_to_invite);

            return $invitation;
        });
    }

    /**
     * Ensure the user is not already in the collection.
     *
     * @param  Collection  $collection
     * @param  string  $email
     * @return void
     * @throws \App\Exceptions\ConflictException
     */
    private function ensureNotAlreadyInCollection(Collection $collection, string $email): void
    {
        if ($collection->users()->where('email', $email)->exists()) {
            throw new ConflictException('User already in collection');
        }
    }

    /**
     * Ensure the user has not a pending invitation.
     *
     * @param  Collection  $collection
     * @param  string  $email
     * @return void
     * @throws \App\Exceptions\ConflictException
     */
    private function ensureNoPendingInvitation(Collection $collection, string $email): void
    {
        $pending = Invitation::findPending($collection, $email);

        if ($pending && !$pending->isExpired()) {
            throw new ConflictException('User already has a pending invitation');
        }

        $pending?->delete();
    }

    /**
     * Generate a random token.
     *
     * @return string
     */
    private function generateToken(): string
    {
        return Str::random(40);
    }

    /**
     * Send an invitation mail to the user.
     *
     * @param  string  $email
     * @param  Collection  $collection
     * @param  string  $token
     * @param  bool  $is_new_user
     * @return void
     */
    private function sendInvitationMail(string $email, Collection $collection, string $token, bool $is_new_user)
    {
        Mail::to($email)->send(new InvitationMail($collection, $token, is_new_user: $is_new_user));
    }

    /**
     * Create an invitation.
     *
     * @param  Collection  $collection
     * @param  string  $email
     * @param  string  $token
     * @return \App\Models\Invitation
     */
    private function createInvitation(Collection $collection, string $email, string $token): Invitation
    {
        return Invitation::create([
            'collection_id' => $collection->id,
            'email'         => $email,
            'token'         => $token,
        ]);
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
        if (!$user_to_remove = User::where('email', $email)->first()) {
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
