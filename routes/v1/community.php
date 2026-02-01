<?php

use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ModerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('communities')->group(function () {
    Route::get('/', [CommunityController::class, 'listCommunities']);
    Route::get('/{community}', [CommunityController::class, 'communityDetail']);

    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::post('/', [CommunityController::class, 'createCommunity']);

        Route::middleware(['community.admin'])->group(function () {
            Route::put('/{community}', [CommunityController::class, 'updateCommunity']);
            Route::delete('/{community}', [CommunityController::class, 'deleteCommunity']);
        });

        Route::middleware(['role:admin'])->group(function () {
            Route::post('/{community}/takedown', [ModerationController::class, 'takeDownCommunity']);
            Route::post('/{community}/restore', [ModerationController::class, 'restoreCommunity']);
        });
    });
});
