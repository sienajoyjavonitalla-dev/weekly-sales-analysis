<?php

namespace App\Policies;

use App\Models\MarketplaceFee;
use App\Models\User;

class MarketplaceFeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function view(User $user, MarketplaceFee $marketplaceFee): bool
    {
        return $user->isAnalyst();
    }

    public function create(User $user): bool
    {
        return $user->isAnalyst();
    }

    public function update(User $user, MarketplaceFee $marketplaceFee): bool
    {
        return $user->isAdmin()
            || ($user->isAnalyst() && $marketplaceFee->created_by_user_id === $user->id);
    }

    public function delete(User $user, MarketplaceFee $marketplaceFee): bool
    {
        return $this->update($user, $marketplaceFee);
    }
}
