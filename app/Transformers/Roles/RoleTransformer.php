<?php

namespace App\Transformers\Roles;

use App\Models\Role;
use League\Fractal\TransformerAbstract;

class RoleTransformer extends TransformerAbstract
{
    /**
     * Определяет, включать ли подробные данные (временные метки и права).
     */
    private bool $detailed;

    public function __construct(bool $detailed = false)
    {
        $this->detailed = $detailed;
    }

    public function transform(Role $role): array
    {
        $baseData = [
            'id' => (int) $role->id,
            'name' => $role->name,
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
            'deleted_at' => $role->deleted_at?->toISOString(),
        ];

        if (! $this->detailed) {
            return $baseData;
        }

        return array_merge($baseData, [
            'permissions' => $this->getPermissions($role),
        ]);
    }

    private function getPermissions(Role $role): array
    {
        return $role->permissions->map(fn ($permission) => [
            'id' => (int) $permission->id,
            'name' => $permission->name,
            'translate' => $permission->translate,
        ])->toArray();
    }
}
