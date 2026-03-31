<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class ContactMessagePolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return false;
    }
}
