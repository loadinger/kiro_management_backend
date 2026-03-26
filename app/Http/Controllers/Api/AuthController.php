<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        if (! $token = $guard->attempt($credentials)) {
            return $this->error('邮箱或密码错误', 401);
        }

        return $this->success($this->tokenPayload($token));
    }

    public function refresh(): JsonResponse
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        return $this->success($this->tokenPayload($guard->refresh()));
    }

    public function logout(): JsonResponse
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();
        return $this->success(null, '已退出登录');
    }

    public function me(): JsonResponse
    {
        return $this->success(auth('api')->user());
    }

    private function tokenPayload(string $token): array
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $guard->factory()->getTTL() * 60,
        ];
    }
}
