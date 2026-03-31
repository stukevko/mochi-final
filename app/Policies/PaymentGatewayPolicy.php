<?php

namespace App\Policies;

use App\Models\PaymentGateway;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class PaymentGatewayPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, PaymentGateway $paymentGateway): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, PaymentGateway $paymentGateway): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, PaymentGateway $paymentGateway): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, PaymentGateway $paymentGateway): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, PaymentGateway $paymentGateway): bool
    {
        return $this->adminAuthorized($user);
    }
}
