<?php

namespace App\Policies;

use App\Models\PriceHistory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriceHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_price_history');
    }

    public function view(User $user, PriceHistory $priceHistory): bool
    {
        return $user->can('view_price_history');
    }

    // PriceHistory is created automatically via services - no direct create/update/delete
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PriceHistory $priceHistory): bool
    {
        return false;
    }

    public function delete(User $user, PriceHistory $priceHistory): bool
    {
        return false;
    }
}