<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        try {
            if (! Auth::guard('sanctum')->check()) {
                return new JsonResponse(
                    [
                        'error' => 'Unauthorized',
                        'message' => 'Invalid or expired token',
                    ],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user = Auth::guard('sanctum')->user();

            app()->singleton(
                'user_data',
                fn () => $user
            );

            return $next($request);

        } catch (Throwable $e) {
            return new JsonResponse(
                [
                    'error' => 'Authentication failed',
                    'message' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTrace() : [],
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
