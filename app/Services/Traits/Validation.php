<?php

namespace App\Services\Traits;

use InvalidArgumentException;

trait Validation
{
    protected function validateId(int|string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException('ID cannot be empty');
        }
    }

    protected function validateField(string $field): void
    {
        if (empty($field)) {
            throw new InvalidArgumentException('Field name cannot be empty');
        }
    }

    protected function validateData(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data array cannot be empty');
        }
    }

    protected function sanitizeData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];

        return array_diff_key($data, array_flip($sensitiveFields));
    }
}
