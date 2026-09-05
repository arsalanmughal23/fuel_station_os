<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockAdjustmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_adjustments');
    }

    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('view_stock_adjustments');
    }

    public function create(User $user): bool
    {
        return $user->can('create_stock_adjustments');
    }

    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('edit_stock_adjustments');
    }

    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('delete_stock_adjustments');
    }
}