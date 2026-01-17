<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'listUsers']);
    Route::get('/{id}', [UserController::class, 'userDetails']);
});

Route::middleware(['auth:api', 'check.token.version'])->group(function () {
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::delete('/profile', [UserController::class, 'deleteUser']);
});
