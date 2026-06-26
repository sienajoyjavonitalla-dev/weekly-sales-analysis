<?php

namespace App\Policies;

use App\Models\UploadedFile;
use App\Models\User;

class UploadedFilePolicy
{
    public function view(User $user, UploadedFile $uploadedFile): bool
    {
        return $user->isAnalyst();
    }

    public function create(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function delete(User $user, UploadedFile $uploadedFile): bool
    {
        return $user->isAdmin()
            || ($user->isAnalyst()
                && $uploadedFile->importBatch?->created_by_user_id === $user->id
                && $uploadedFile->importBatch?->status === 'draft');
    }
}
