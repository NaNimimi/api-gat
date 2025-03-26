<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\User\UserService;
use App\Transformers\Users\UserTransformer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends ApiController
{
    public function __construct(readonly UserService $userService) {
        $this->userService->setTransformer(new UserTransformer(true));
    }

    /**
     * @throws Throwable
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data) {
            $user = User::where('username', $data['username'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return $this->respondError(['message' => 'Логин или пароль не верны'], Response::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken(
                'auth-token',
                ['*'],
                now()->addMinutes(config('sanctum.expiration'))
            );

            $token->accessToken->forceFill([
                'device_type' => 'web',
                'ip_address' => request()->getClientIp(),
            ])->save();

            $expiresAt = null;

            if ($expiration = config('sanctum.expiration')) {
                $expiresAt = Carbon::now()->addMinutes(($expiration))->toDateTimeString();
            }

            return $this->respondSuccess([
                'ok' => true,
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt,
            ] + $this->userService->find($user->id));
        }

        return $this->respondError(['message' => 'Логин или пароль не верны'], Response::HTTP_UNAUTHORIZED);

    }

    /**
     * @throws Throwable
     */
    public function me(): JsonResponse
    {
        return $this->respondSuccess($this->userService->find(auth()->user()->id));
    }

    public function logout(): array
    {
        $user = app('user_data');

        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        return ['message' => 'Successfully logged out'];
    }
}
