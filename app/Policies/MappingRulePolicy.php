<?php

namespace App\Policies;

use App\Models\MappingRule;
use App\Models\User;

class MappingRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function view(User $user, MappingRule $mappingRule): bool
    {
        return $user->isAnalyst();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MappingRule $mappingRule): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MappingRule $mappingRule): bool
    {
        return $user->isAdmin();
    }
}
