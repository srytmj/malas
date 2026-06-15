<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Series;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeriesPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Series');
    }

    public function view(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('View:Series');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Series');
    }

    public function update(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('Update:Series');
    }

    public function delete(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('Delete:Series');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Series');
    }

    public function restore(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('Restore:Series');
    }

    public function forceDelete(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('ForceDelete:Series');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Series');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Series');
    }

    public function replicate(AuthUser $authUser, Series $series): bool
    {
        return $authUser->can('Replicate:Series');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Series');
    }

}