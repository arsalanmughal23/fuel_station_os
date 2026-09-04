<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('view_products');
    }

    public function create(User $user): bool
    {
        return $user->can('create_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('edit_products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('delete_products');
    }
}