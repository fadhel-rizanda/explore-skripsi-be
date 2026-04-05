<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'attachments',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::get('/{document}/download-url', [AttachmentController::class, 'generateDownloadUrl'])->middleware('throttle:read');

    Route::middleware(['throttle:write'])->group(function () {
        Route::post('/presigned-url', [AttachmentController::class, 'generatePresignedUrl']);
        Route::patch('/{document}/confirm', [AttachmentController::class, 'confirmUpload']);
        Route::delete('/{document}', [AttachmentController::class, 'deleteDocument']);
    });
});
