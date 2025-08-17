<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserProduct;

final class UserProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function view(User $user, UserProduct $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, UserProduct $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function delete(User $user, UserProduct $product): bool
    {
        return $user->id === $product->user_id;
    }
}