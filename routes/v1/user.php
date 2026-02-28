<?php

use App\Http\Controllers\ModerationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::get('/channels', [UserController::class, 'userChannels']);
    });

    Route::get('/', [UserController::class, 'listUsers']);
    Route::get('/{user}', [UserController::class, 'userDetails']);

    Route::middleware(['auth:api', 'check.token.version', 'role:admin'])->group(function () {
        Route::post('/{user}/deactivate', [ModerationController::class, 'deactivateUser']);
        Route::post('/{user}/activate', [ModerationController::class, 'activateUser']);
    });
});

Route::middleware(['auth:api', 'check.token.version'])->group(function () {
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::delete('/profile', [UserController::class, 'deleteUser']);
});
