<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class OrderPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, Order $order): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $this->adminAuthorized($user);
    }
}
