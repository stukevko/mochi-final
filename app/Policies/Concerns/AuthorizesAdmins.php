<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesAdmins
{
    protected function adminAuthorized(User $user): bool
    {
        return $user->isAdmin() && ($user->is_active ?? false);
    }
}

