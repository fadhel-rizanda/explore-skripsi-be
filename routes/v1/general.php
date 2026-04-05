<?php

use App\Http\Controllers\DistrictController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\RegencyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'general',
    'middleware' => ['throttle:read'],
], function () {
    Route::get('/statuses', [StatusController::class, 'listStatuses']);
    Route::get('/tags', [TagController::class, 'listTags']);
    Route::get('/roles', [RoleController::class, 'listRoles']);
    Route::get('/provinces', [ProvinceController::class, 'listProvinces']);
    Route::get('/provinces/{province}/regencies', [RegencyController::class, 'listRegencies']);
    Route::get('/regencies/{regency}/districts', [DistrictController::class, 'listDistricts']);

    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::get('/users', [UserController::class, 'userOptions']);
    });
});
