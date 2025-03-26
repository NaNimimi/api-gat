<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    protected function respondSuccess($data = []): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$data,
        ], Response::HTTP_OK);
    }

    protected function respondError($data = [], $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'ok' => false,
            ...$data,
        ], $status);
    }

    protected function respondCreated($data = []): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$data,
        ], Response::HTTP_CREATED);
    }

    protected function respondNoContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function failNotFound(string $message = 'Not Found'): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => app()->hasDebugModeEnabled() ? $message : 'Not Found',
        ], Response::HTTP_NOT_FOUND);
    }

    protected function failValidation(array $errors): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'Validation failed',
            'errors' => app()->hasDebugModeEnabled() ? $errors : [],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function failServerError(string $message = 'Internal Server Error'): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => app()->hasDebugModeEnabled() ? $message : 'Internal Server Error',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
