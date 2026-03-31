<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class ProductPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $this->adminAuthorized($user);
    }
}
