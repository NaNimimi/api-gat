<?php

namespace App\Http\Requests\Contracts;

interface ValidationContract
{
    public function rules(): array;

    public function authorize(): bool;

    public function getValidationRules(): array;

    public function sanitizeInput(array $input): array;

    public function validateAndTransform(array $data): array;
}
