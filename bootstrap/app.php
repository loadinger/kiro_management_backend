<?php

use App\Exceptions\AppException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 统一转换为信封格式
        $envelope = fn(int $code, string $message, mixed $data = null): JsonResponse =>
            response()->json(['code' => $code, 'message' => $message, 'data' => $data]);

        $exceptions->render(function (AuthenticationException $e) use ($envelope) {
            return $envelope(401, '未认证，请先登录');
        });

        $exceptions->render(function (ValidationException $e) use ($envelope) {
            $message = collect($e->errors())->flatten()->first() ?? '参数验证失败';
            return $envelope(422, $message);
        });

        $exceptions->render(function (NotFoundHttpException $e) use ($envelope) {
            return $envelope(404, '资源不存在');
        });

        $exceptions->render(function (AppException $e) use ($envelope) {
            return $envelope($e->errorCode, $e->getMessage());
        });
    })->create();
