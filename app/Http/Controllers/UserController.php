<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\UserRequest;
use App\Services\User\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    public function index(UserRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $perPage = $filters['per_page'] ?? 15;

            return $this->respondSuccess(
                $this->userService->paginate($perPage)
            );
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function all(): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this->userService->getFully()
            );
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function show($user): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this->userService->getById($user)
            );
        } catch (ApiException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            return $this->respondCreated(
                $this->userService->createFromDTO($request->toDTO())
            );
        } catch (ApiException $e) {
            return $this->failValidation(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function update(UserRequest $request, $user): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this->userService->updateFromDTO($user, $request->toDTO())
            );
        } catch (ApiException $e) {
            return match ($e->getCode()) {
                404 => $this->failNotFound($e->getMessage()),
                422 => $this->failValidation(['error' => $e->getMessage()]),
                default => $this->failServerError($e->getMessage()),
            };
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function destroy(UserRequest $request, $user): JsonResponse
    {
        try {
            $force = $request->validated('force', false);

            $force
                ? $this->userService->forceDelete($user)
                : $this->userService->delete($user);

            return $this->respondNoContent();

        } catch (ApiException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
