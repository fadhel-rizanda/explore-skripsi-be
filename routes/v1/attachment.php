<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'attachments',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::post('/presigned', [AttachmentController::class, 'generatePresignedUrl']);
    Route::post('/{document}/confirm', [AttachmentController::class, 'confirmUpload']);
    Route::get('/{document}/download', [AttachmentController::class, 'generateDownloadUrl']);
    Route::delete('/{document}', [AttachmentController::class, 'deleteDocument']);
});
