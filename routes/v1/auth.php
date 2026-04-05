<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => ['throttle:register-login']], function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::group(['middleware' => ['throttle:read']], function () {
        Route::get('/{provider}', [AuthController::class, 'redirectToProvider']);
        Route::get('/{provider}/callback', [AuthController::class, 'handleProviderCallback']);
    });

    Route::group(['middleware' => ['throttle:write']], function () {
        Route::post('/provider', [AuthController::class, 'loginWithProvider']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        Route::middleware(['auth:api'])->group(function () {
            Route::post('/activation-code/resend', [AuthController::class, 'resendActivationCode']);
            Route::post('/activation-code/verify', [AuthController::class, 'validateActivationCode']);
        });

        // harus 2 biar semua token yang ke invalid
        Route::middleware(['auth:api', 'check.token.version'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });
    });
});
