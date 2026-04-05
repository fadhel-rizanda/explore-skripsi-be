<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'reports',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::middleware(['throttle:write'])->group(function () {
        Route::post('/', [ReportController::class, 'createReport']);
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::middleware(['throttle:read'])->group(function () {
            Route::get('/', [ReportController::class, 'listReports']);
            Route::get('/{report}', [ReportController::class, 'reportDetail']);
        });

        Route::middleware(['throttle:write'])->group(function () {
            Route::delete('/{report}', [ReportController::class, 'deleteReport']);
            Route::put('/{report}/status/{status}', [ReportController::class, 'updateReportStatus']);
        });
    });
});
