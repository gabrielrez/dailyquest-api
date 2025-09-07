<?php

namespace App\Http\Services;

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

    public function addOrInviteUser(Collection $collection, string $user_email)
    {
        //
    }
}
