<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $user->isAnalyst();
    }

    public function create(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function update(User $user, ImportBatch $importBatch): bool
    {
        return $user->isAdmin()
            || ($user->isAnalyst()
                && $importBatch->created_by_user_id === $user->id
                && $importBatch->status === 'draft');
    }

    public function finalize(User $user, ImportBatch $importBatch): bool
    {
        return $user->isAdmin()
            || ($user->isAnalyst() && $importBatch->created_by_user_id === $user->id);
    }

    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return $user->isAdmin() && $importBatch->status === 'draft';
    }
}
