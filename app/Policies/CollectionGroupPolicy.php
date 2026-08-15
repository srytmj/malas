<?php

namespace App\Policies;

use App\Models\CollectionGroup;
use App\Models\User;

class CollectionGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CollectionGroup $group): bool
    {
        return $user->isAdmin() || $group->user_id === $user->id;
    }

    public function delete(User $user, CollectionGroup $group): bool
    {
        return $user->isAdmin() || $group->user_id === $user->id;
    }
}
