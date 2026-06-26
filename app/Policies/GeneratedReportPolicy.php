<?php

namespace App\Policies;

use App\Models\GeneratedReport;
use App\Models\User;

class GeneratedReportPolicy
{
    public function view(User $user, GeneratedReport $generatedReport): bool
    {
        return $user->isAnalyst();
    }

    public function create(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function download(User $user, GeneratedReport $generatedReport): bool
    {
        return $user->isAnalyst() && $generatedReport->status === 'completed';
    }

    public function delete(User $user, GeneratedReport $generatedReport): bool
    {
        return $user->isAdmin();
    }
}
