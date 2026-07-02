<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Volume;

class VolumePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Volume $volume): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Volume $volume): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Volume $volume): bool
    {
        return $user->isAdmin();
    }
}
