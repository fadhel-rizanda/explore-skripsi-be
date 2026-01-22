<?php

use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('pets')->group(function () {
    Route::get('/', [PetController::class, 'index']);
    Route::get('/{id}', [PetController::class, 'show']);
    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::post('/', [PetController::class, 'store']);
        Route::put('/{id}', [PetController::class, 'update']);
        Route::delete('/{id}', [PetController::class, 'destroy']);

        Route::middleware(['role:admin'])->group(function () {
            Route::post('/{pet}/takedown', [ModerationController::class, 'takeDownPet']);
            Route::post('/{pet}/restore', [ModerationController::class, 'restorePet']);
        });
    });
});
