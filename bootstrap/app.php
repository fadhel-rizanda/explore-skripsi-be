<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(function (Request $request) {
            return null;
        });
        $middleware->alias([
            'check.token.version' => \App\Http\Middleware\CheckTokenVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            \Illuminate\Support\Facades\Log::error('403 AccessDenied caught', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(
                [
                    'error' => true,
                    'status' => '403 Forbidden',
                    'message' => $e->getMessage() ?: 'You are not authorized to perform this action.',
                    'data' => [],
                ],
                403,
                [],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            );
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'status' => 401,
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'data' => [],
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($request->is('*')) {
                return response()->json(
                    [
                        'error' => true,
                        'status' => '404 Error!',
                        'message' => 'Record not found.',
                        'data' => [],
                    ],
                    404,
                    [],
                    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                );
            }
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return str_starts_with($path = $request->path(), 'api')
                && (strlen($path) === 3 || $path[3] === '/');
        });

        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
