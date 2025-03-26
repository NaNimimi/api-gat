<?php

namespace App\Services\Traits;

use App\Models\User;

trait UserRolePermissionTrait
{
    abstract protected function handle(callable $operation, string $errorMessage, array $context = []): mixed;

    abstract protected function getUser(int|string $id): User;

    public function getUserRoles(int|string $id): array
    {
        return $this->handle(
            fn () => $this->getUser($id)->roles->pluck('name')->toArray(),
            "Error fetching roles for user ID: $id",
            ['id' => $id]
        );
    }

    public function getUserPermissions(int|string $id): array
    {
        return $this->handle(
            fn () => $this->getUser($id)->getAllPermissions()->pluck('name')->toArray(),
            "Error fetching permissions for user ID: $id",
            ['id' => $id]
        );
    }

    public function hasRole(int|string $id, string $role): bool
    {
        return $this->handle(
            fn () => $this->getUser($id)->hasRole($role),
            "Error checking role for user ID: $id",
            ['id' => $id, 'role' => $role]
        );
    }

    public function hasPermission(int|string $id, string $permission): bool
    {
        return $this->handle(
            fn () => $this->getUser($id)->hasPermissionTo($permission),
            "Error checking permission for user ID: $id",
            ['id' => $id, 'permission' => $permission]
        );
    }
}
