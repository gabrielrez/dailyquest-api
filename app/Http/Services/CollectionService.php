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

    public function addOrInviteUser(Collection $collection, string $email)
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($collection->users()->where('user_id', $user->id)->exists()) {
                throw new ConflictException('User already in collection');
            }

            $collection->users()->attach($user->id);

            // TODO: Notify, somehow, the user that he was removed from the collection

            return 'User added to collection';
        }

        // TODO: Send email to user to invite to the collection

        throw new NotFoundException('User not found'); // for now

        return 'Invitation sent to user';
    }

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

        // TODO: Notify, somehow, the user that he was removed from the collection

        return 'User removed from collection';
    }
}
