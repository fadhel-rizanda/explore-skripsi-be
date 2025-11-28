<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
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
        $this->renderable(function (HttpException $e, $request) {
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

        // return json when path start with `api`
        $this->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return str_starts_with($path = $request->path(), 'api')
                && (strlen($path) === 3 || $path[3] === '/');
        });

        $this->reportable(function (\Throwable $e) {});
    }
}
