<?php

namespace App\Transformers\Users;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * Определяет, включать ли подробные данные (роли, права, метки времени).
     */
    private bool $detailed;

    public function __construct(bool $detailed = false)
    {
        $this->detailed = $detailed;
    }

    public function transform(User $user): array
    {
        $baseData = [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'username' => (string) $user->username,
        ];

        if (! $this->detailed) {
            return $baseData;
        }

        return array_merge($baseData, [
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'deleted_at' => $user->deleted_at?->toISOString(),
            'roles' => $this->getRoles($user),
            'permissions' => $user->hasRole('Admin')
                ? ['manage-all']
                : $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }

    private function getRoles(User $user): array
    {
        return $user->roles->map(fn ($role) => [
            'id' => (int) $role->id,
            'name' => $role->name,
        ])->toArray();
    }
}
