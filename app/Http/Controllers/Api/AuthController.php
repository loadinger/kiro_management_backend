<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        if (! $token = $guard->attempt($request->validated())) {
            return $this->error('邮箱或密码错误', 401);
        }

        return $this->success($this->tokenPayload($token));
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $this->success($this->tokenPayload($guard->refresh()));
    }

    public function logout(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return $this->success(null, '已退出登录');
    }

    public function me(): JsonResponse
    {
        return $this->success(new UserResource(auth('api')->user()));
    }

    private function tokenPayload(string $token): array
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ];
    }
}
