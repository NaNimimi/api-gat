<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\RoleRequest;
use App\Services\Role\RoleServiceInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

class RoleController extends ApiController
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    public function index(RoleRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $perPage = $filters['per_page'] ?? 15;

            return $this->respondSuccess(
                $this-> roleService->paginate($perPage)
            );
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function all(): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this-> roleService->getFully()
            );
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function show($user): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this-> roleService->getById($user)
            );
        } catch (ApiException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function store(RoleRequest $request): JsonResponse
    {
        try {
            return $this->respondCreated(
                $this-> roleService->createFromDTO($request->toDTO())
            );
        } catch (ApiException $e) {
            return $this->failValidation(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function update(RoleRequest $request, $user): JsonResponse
    {
        try {
            return $this->respondSuccess(
                $this-> roleService->updateFromDTO($user, $request->toDTO())
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

    public function destroy(RoleRequest $request, $user): JsonResponse
    {
        try {
            $force = $request->validated('force', false);

            $force
                ? $this-> roleService->forceDelete($user)
                : $this-> roleService->delete($user);

            return $this->respondNoContent();

        } catch (ApiException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
