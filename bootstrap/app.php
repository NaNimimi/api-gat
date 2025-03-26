<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\LogSlowRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

use Symfony\Component\HttpFoundation\Response;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            LogSlowRequests::class,
        ]);

        $middleware->alias([
            'slow.log' => LogSlowRequests::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            $statusCode = $response->getStatusCode();


            $class = match ($statusCode) {
                Response::HTTP_FORBIDDEN => ApiException::forbidden(),
                Response::HTTP_UNAUTHORIZED => ApiException::invalidToken(),
                Response::HTTP_NOT_FOUND => ApiException::notFound('Стараница'),
                Response::HTTP_UNPROCESSABLE_ENTITY => ApiException::validation(),
                Response::HTTP_METHOD_NOT_ALLOWED => ApiException::httpError(),
                default => ApiException::badRequest(),
            };


            return $class->render();
        });
    })->create();
