<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'reports',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::post('/', [ReportController::class, 'createReport']);

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/', [ReportController::class, 'listReports']);
        Route::get('/{report}', [ReportController::class, 'reportDetail']);
        Route::delete('/{report}', [ReportController::class, 'deleteReport']);
        Route::put('/{report}/status/{status}', [ReportController::class, 'updateReportStatus']);

        Route::post('/{report}/users/{user}/deactivate', [ReportController::class, 'deactivateUser']);
        Route::post('/{report}/users/{user}/activate', [ReportController::class, 'activateUser']);

        Route::post('/{report}/pets/{pet}/takedown', [ReportController::class, 'takeDownPet']);
        Route::post('/{report}/pets/{pet}/restore', [ReportController::class, 'restorePet']);

        Route::post('/{report}/posts/{post}/takedown', [ReportController::class, 'takeDownPost']);
        Route::post('/{report}/posts/{post}/restore', [ReportController::class, 'restorePost']);

        Route::post('/{report}/communities/{community}/takedown', [ReportController::class, 'takeDownCommunity']);
        Route::post('/{report}/communities/{community}/restore', [ReportController::class, 'restoreCommunity']);
    });
});
