<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Volume;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolumePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Volume');
    }

    public function view(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('View:Volume');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Volume');
    }

    public function update(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('Update:Volume');
    }

    public function delete(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('Delete:Volume');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Volume');
    }

    public function restore(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('Restore:Volume');
    }

    public function forceDelete(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('ForceDelete:Volume');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Volume');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Volume');
    }

    public function replicate(AuthUser $authUser, Volume $volume): bool
    {
        return $authUser->can('Replicate:Volume');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Volume');
    }

}