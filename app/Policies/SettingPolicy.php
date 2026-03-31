<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\Concerns\AuthorizesAdmins;

class SettingPolicy
{
    use AuthorizesAdmins;

    public function viewAny(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->adminAuthorized($user);
    }

    public function create(User $user): bool
    {
        return $this->adminAuthorized($user);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->adminAuthorized($user);
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $this->adminAuthorized($user);
    }

    public function restore(User $user, Setting $setting): bool
    {
        return $this->adminAuthorized($user);
    }

    public function forceDelete(User $user, Setting $setting): bool
    {
        return $this->adminAuthorized($user);
    }
}
