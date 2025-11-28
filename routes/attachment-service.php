<?php

use \App\Http\Controllers\UploadController;
use \Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'attachments',
    'middleware' => ['auth:api', 'check.token.version']
], function () {
    Route::post('/presigned', [UploadController::class, 'generatePresignedUrl']);
    Route::post('/confirm/{documentId}', [UploadController::class, 'confirmUpload']);
    Route::get('/download/{documentId}', [UploadController::class, 'generateDownloadUrl']);
    Route::delete('/{documentId}', [UploadController::class, 'deleteDocument']);
});
