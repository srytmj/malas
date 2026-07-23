<?php

namespace App\Policies;

use App\Models\StorageSetting;
use App\Models\User;

class StorageSettingPolicy
{
    public function view(User $user, StorageSetting $storageSetting): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, StorageSetting $storageSetting): bool
    {
        return $user->isSuperAdmin();
    }
}
