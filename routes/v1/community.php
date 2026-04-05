<?php

use App\Enums\ModelReferenceEnum;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ModerationController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'communities',
    'middleware' => ['throttle:read'],
], function () {
    Route::get('/', [CommunityController::class, 'listCommunities']);
    Route::get('/{community}', [CommunityController::class, 'communityDetail'])->middleware('model.isActive:' . ModelReferenceEnum::COMMUNITY->value);

    Route::middleware(['auth:api', 'check.token.version'])->group(function () {
        Route::middleware(['throttle:write'])->group(function () {
            Route::post('/', [CommunityController::class, 'createCommunity']);
            Route::post('/{community}/follow', [CommunityController::class, 'followCommunity'])->middleware('model.isActive:' . ModelReferenceEnum::COMMUNITY->value);

            Route::middleware('community.admin')->group(function () {
                Route::put('/{community}', [CommunityController::class, 'updateCommunity']);
                Route::delete('/{community}', [CommunityController::class, 'deleteCommunity']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/{community}/takedown', [ModerationController::class, 'takeDownCommunity']);
                Route::post('/{community}/restore', [ModerationController::class, 'restoreCommunity']);
            });
        });
    });
});
