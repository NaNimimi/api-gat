<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends ApiException
{
    protected function getDefaultErrorCode(): string
    {
        return 'FORBIDDEN';
    }

    public function __construct(string $message = 'Доступ запрещен')
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN);
    }
}
