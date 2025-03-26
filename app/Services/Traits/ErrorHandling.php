<?php

namespace App\Services\Traits;

use App\Exceptions\ApiException;
use App\Exceptions\ForbiddenException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

trait ErrorHandling
{
    protected string $logChannel = 'telegram';

    /**
     * @throws Throwable
     */
    protected function handle(callable $operation, string $errorMessage, array $context = []): mixed
    {
        try {
            return $operation();
        } catch (Throwable $e) {
            $this->logError($errorMessage, $e, $context);
            throw $this->wrapException($e, $errorMessage, $context);
        }
    }

    protected function handleSafely(callable $operation, mixed $default = null): mixed
    {
        try {
            return $operation();
        } catch (Throwable $e) {
            $this->logError('Operation failed silently', $e);

            return $default;
        }
    }

    protected function logError(string $message, Throwable $e, array $context = []): void
    {
        Log::channel($this->logChannel)->debug(['exception' => [
            'exception' => get_class($e),
            'message' => $e->getMessage().'|'.$message,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $context,
            'trace' => $e->getTraceAsString(),
        ]]);
    }

    protected function wrapException(Throwable $e, string $errorMessage, array $context = []): Throwable
    {
        $debugInfo = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $context,
        ];

        return match (true) {
            $e instanceof ApiException => $e,
            $e instanceof ModelNotFoundException => ApiException::notFound(class_basename($e->getModel())),
            $e instanceof QueryException => new ApiException('Database error', 500, $e),
            $e instanceof ValidationException => ApiException::validation($e->errors())->withDebug($debugInfo),
            $e instanceof AuthenticationException => new ApiException('Authentication required', 401, $e),
            $e instanceof AuthorizationException => ForbiddenException::withDebug($debugInfo),
            default => new ApiException($errorMessage, 500, $e)
        };
    }

    public function setLogChannel(string $channel): static
    {
        $this->logChannel = $channel;

        return $this;
    }
}
