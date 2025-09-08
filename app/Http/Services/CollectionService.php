<?php

namespace App\Http\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Http\Request;

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
     * Add a user to a collection and notify them, or invite them to the collection if they're not already in it.
     *
     * @param  Collection  $collection  The collection to add the user to.
     * @param  string      $email       The email of the user to add.
     * @return string
     */
    public function addOrInviteUser(Collection $collection, string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // TODO: Send email to user to invite to the collection
            return 'Invitation sent to user';
        }

        if ($collection->users()->where('user_id', $user->id)->exists()) {
            throw new ConflictException('User already in collection');
        }

        $collection->users()->attach($user->id); // just for now, soon the user will be invited and has to accept

        // TODO: Notify, somehow, the user that he was added to the collection

        return 'User added to collection';
    }

    /**
     * Remove a user from a collection and notify them.
     *
     * @param  Collection  $collection  The collection to remove the user from.
     * @param  string      $email       The email of the user to remove.
     * @return string
     */
    public function removeOrNotifyUser(Collection $collection, string $email)
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

        // TODO: Notify, somehow, the user that he was removed from the collection (email and app notification)

        return 'User removed from collection';
    }
}
