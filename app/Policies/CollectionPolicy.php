<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function ownerAccess(User $user, Collection $collection): bool
    {
        return $collection->belongsToUser($user, owner_only: true);
    }

    public function collaboratorAccess(User $user, Collection $collection): bool
    {
        return $collection->belongsToUser($user);
    }
}
