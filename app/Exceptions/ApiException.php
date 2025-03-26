<?php

namespace App\Exceptions;

use App\Exceptions\Constants\ErrorCodes;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiException extends Exception
{
    protected string $errorCode;

    protected array $errors = [];

    protected ?array $debug = null;

    public function __construct(
        string $message = 'Внутренняя ошибка сервера',
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $this->getDefaultErrorCode();
    }

    public static function error(
        string $message = 'Внутренняя ошибка сервера',
        string $errorCode = ErrorCodes::INTERNAL_ERROR,
        int $status = Response::HTTP_INTERNAL_SERVER_ERROR
    ): static {
        $instance = new static($message, $status);
        $instance->errorCode = $errorCode;

        return $instance;
    }

    public static function notFound(string $entity = 'Запись'): static
    {
        return static::error(
            "$entity не найден(а)",
            ErrorCodes::NOT_FOUND,
            Response::HTTP_NOT_FOUND
        );
    }

    public static function forbidden(): static
    {
        return static::error(
            'Доступ запрещен',
            ErrorCodes::FORBIDDEN,
            Response::HTTP_FORBIDDEN
        );
    }

    public static function invalidToken(): static
    {
        return static::error(
            'Токен эррор',
            ErrorCodes::INVALID_TOKEN,
            Response::HTTP_UNAUTHORIZED
        );
    }

    public static function badRequest($message = 'Bad request'): static
    {
        return static::error(
            $message,
            ErrorCodes::HTTP_ERROR,
            Response::HTTP_BAD_REQUEST
        );
    }

    public static function httpError($message = 'Method not allowed'): static
    {
        return static::error(
            $message,
            ErrorCodes::HTTP_ERROR,
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    }

    public static function validation(array $errors = []): static
    {
        $instance = static::error(
            'Ошибка валидации данных',
            ErrorCodes::VALIDATION_ERROR,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
        $instance->withErrors($errors);

        return $instance;
    }

    protected function getDefaultErrorCode(): string
    {
        return ErrorCodes::INTERNAL_ERROR;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function withErrorCode(string $code): self
    {
        $this->errorCode = $code;

        return $this;
    }

    public function withError(string $field, array|string $messages): self
    {
        $this->errors[$field] = (array) $messages;

        return $this;
    }

    public function withErrors(array $errors): self
    {
        foreach ($errors as $field => $messages) {
            $this->withError($field, $messages);
        }

        return $this;
    }

    public function withDebug(array $debug): self
    {
        if (config('app.debug')) {
            $this->debug = array_merge($this->debug ?? [], $debug);
        }

        return $this;
    }

    public function addDebugInfo(string $key, mixed $value): self
    {
        return $this->withDebug([$key => $value]);
    }

    public function render(): JsonResponse
    {
        $data = [
            'ok' => false,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'status' => $this->getCode(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (! empty($this->errors)) {
            $data['errors'] = $this->errors;
        }

        if (config('app.debug') && $this->debug) {
            $data['debug'] = $this->debug;
        }

        return new JsonResponse($data, $this->getCode());
    }
}
