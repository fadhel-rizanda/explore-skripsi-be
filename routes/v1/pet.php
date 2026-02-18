<?php

use App\Enums\ModelReferenceEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\AdoptionController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('pets')->group(function () {
    Route::get('/', [PetController::class, 'index']);
    Route::get('/{pet}', [PetController::class, 'show'])->middleware('model.isActive:' . ModelReferenceEnum::PET->value);

    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::middleware(['role:' . RoleEnum::PROVIDER->value . '|' . RoleEnum::ADMIN->value])->group(function () {
            Route::post('/', [PetController::class, 'store']);
            Route::middleware(['model.isActive:' . ModelReferenceEnum::PET->value])->group(function () {
                Route::put('/{pet}', [PetController::class, 'update']);
                Route::delete('/{pet}', [PetController::class, 'destroy']);
            });
        });
        Route::post('/{pet}/adopt', [AdoptionController::class, 'adopt'])->middleware(['role:' . RoleEnum::ADOPTER->value . '|' . RoleEnum::ADMIN->value, 'model.isActive:' . ModelReferenceEnum::PET->value]);
        Route::post('/{pet}/adopt/{adoption}/reject', [AdoptionController::class, 'reject'])->middleware(['adoption.access:' . RoleEnum::PROVIDER->value]);
        Route::post('/{pet}/adopt/{adoption}/cancel', [AdoptionController::class, 'cancel'])->middleware(['adoption.access:' . RoleEnum::ADOPTER->value]);

        Route::middleware(['role:' . RoleEnum::ADMIN->value])->group(function () {
            Route::post('/{pet}/takedown', [ModerationController::class, 'takeDownPet']);
            Route::post('/{pet}/restore', [ModerationController::class, 'restorePet']);
        });
    });
});
