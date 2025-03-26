<?php

namespace App\Services\Role;

use App\DTO\Role\CreateRoleData;
use App\DTO\Role\UpdateRoleData;

interface RoleServiceInterface
{
    public function createFromDTO(CreateRoleData $dto): array;

    public function updateFromDTO(int|string $id, UpdateRoleData $dto): array;
}
