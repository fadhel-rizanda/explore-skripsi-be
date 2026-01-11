<?php

use App\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('pets')->group(function () {
    Route::get('/', [PetController::class, 'index']);
    Route::post('/', [PetController::class, 'store']);
    Route::get('/{id}', [PetController::class, 'show']);
    Route::put('/{id}', [PetController::class, 'update']);
    Route::delete('/{id}', [PetController::class, 'destroy']);
});
