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

        return $this->success($this->tokenPayload($token, $guard->user()));
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        try {
            // setToken allows parsing an expired token for refresh purposes,
            // bypassing the auth:api middleware which would reject expired tokens.
            $newToken = $guard->setToken($guard->getToken())->refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->error('登录已过期，请重新登录', 401);
        } catch (\Throwable $e) {
            return $this->error('Token 无效，请重新登录', 401);
        }

        return $this->success($this->tokenPayload($newToken));
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

    private function tokenPayload(string $token, mixed $user = null): array
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $payload = [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $guard->factory()->getTTL() * 60,
        ];

        if ($user !== null) {
            $payload['user'] = ['id' => $user->id, 'name' => $user->name];
        }

        return $payload;
    }
}
