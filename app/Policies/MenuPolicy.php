<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->isAdmin();
    }
}
