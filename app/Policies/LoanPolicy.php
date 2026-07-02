<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || ($loan->collection && $loan->collection->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || ($loan->collection && $loan->collection->user_id === $user->id);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || ($loan->collection && $loan->collection->user_id === $user->id);
    }
}
