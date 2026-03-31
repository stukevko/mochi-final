<?php

namespace App\Policies;

use App\Models\ProductAttribute;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class ProductAttributePolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, ProductAttribute $productAttribute): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, ProductAttribute $productAttribute): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, ProductAttribute $productAttribute): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, ProductAttribute $productAttribute): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, ProductAttribute $productAttribute): bool
    {
        return $this->adminAuthorized($user);
    }
}
