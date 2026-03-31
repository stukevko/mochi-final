<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class UserPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->adminAuthorized($user);
    }
}
