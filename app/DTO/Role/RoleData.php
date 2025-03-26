<?php

declare(strict_types=1);

namespace App\DTO\Role;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,
        #[Required, ArrayType]
        public readonly array $permissions = [],
    ) {}

    public function gerPermissionIds(): array
    {
        return array_filter(array_map(
            static fn ($role) => is_numeric($role) ? (int) $role : null,
            $this->permissions
        ));
    }

    public function toDatabase(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
