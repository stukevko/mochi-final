<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class CategoryPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->adminAuthorized($user);
    }
}
