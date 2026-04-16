<?php

namespace App\Core\Authorization;

use App\Core\Auth\CurrentUser;

class Authorizer
{
    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly PermissionMap $permissionMap,
        private readonly bool $bypass = false,
    ) {
    }

    public function roles(): array
    {
        return $this->currentUser->roles();
    }

    public function hasRole(string ...$roles): bool
    {
        if ($this->bypass) {
            return true;
        }

        $currentRoles = $this->roles();
        foreach ($roles as $role) {
            if (in_array($role, $currentRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->bypass) {
            return true;
        }

        $permissions = $this->permissionMap->permissionsForRoles($this->roles());
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function permissionForRoute(string $route): ?string
    {
        return $this->permissionMap->permissionForRoute($route);
    }
}
