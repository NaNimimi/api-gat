<?php

declare(strict_types=1);

namespace App\DTO\User;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,
        #[Required, StringType]
        public readonly string $username,
        public readonly ?string $password = null,
        #[Required, ArrayType]
        public readonly array $roles = [],
    ) {}

    public function getRoleIds(): array
    {
        return array_filter(array_map(
            static fn ($role) => is_numeric($role) ? (int) $role : null,
            $this->roles
        ));
    }

    public function toDatabase(): array
    {
        $data = [
            'name' => $this->name,
            'username' => $this->username,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
