<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends ApiException
{
    protected function getDefaultErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    public function __construct(array $errors = [])
    {
        parent::__construct(
            'Ошибка валидации данных',
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
        $this->withErrors($errors);
    }
}
