<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    /**
     * Время в секундах, превышение которого приводит к логированию запроса.
     */
    private const THRESHOLD = 4.0; // секунды

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $end = microtime(true);
        $duration = $end - $start;

        // Логируем, если запрос длится дольше заданного порога
        if ($duration > self::THRESHOLD) {
            Log::channel('telegram')->debug('Slow request detected.',[
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => $duration,
                'ip' => $request->ip(),
                'params' => $request->all(),
            ]);
        }

        return $response;
    }
}
