<?php

namespace App\Exceptions;

use http\Exception\RuntimeException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use \Illuminate\Validation\ValidationException as VException;
class Handler extends ExceptionHandler
{
    protected $dontReport = [
        ApiException::class,
        ValidationException::class,
        NotFoundException::class,
        ForbiddenException::class,
        InvalidTokenException::class,
    ];

    public function register(): void
    {
        $this->renderable(function (ApiException $e) {
            return $e->render();
        });

        $this->renderable(function (VException $e) {
            return (new ValidationException($e->errors()))->render();
        });

        $this->renderable(function (RuntimeException $e) {
            return ApiException::error($e->getMessage())->render();
        });

        $this->renderable(function (HttpException $e) {
            $statusCode = $e->getStatusCode();

            if ($statusCode === 404) {
                return (new NotFoundException('Страница'))->render();
            }

            if ($statusCode === 403) {
                return (new ForbiddenException($e->getMessage()))->render();
            }

            return ApiException::error(
                $e->getMessage(),
                'HTTP_ERROR',
                $statusCode
            )->render();
        });
    }
}
