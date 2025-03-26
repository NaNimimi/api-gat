<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends ApiException
{
    protected function getDefaultErrorCode(): string
    {
        return 'NOT_FOUND';
    }

    public function __construct(string $entity = 'Запись')
    {
        parent::__construct(
            "$entity не найден(а)",
            Response::HTTP_NOT_FOUND
        );
    }
}
