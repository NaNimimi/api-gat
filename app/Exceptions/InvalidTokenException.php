<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidTokenException extends ApiException
{
    protected function getDefaultErrorCode(): string
    {
        return 'INVALID_TOKEN';
    }

    public function __construct(string $message = 'Недействительный токен')
    {
        parent::__construct($message, Response::HTTP_UNAUTHORIZED);
    }
}
