<?php

use \App\Http\Controllers\UploadController;
use \Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/upload/presigned', [UploadController::class, 'generatePresignedUrl']);
    Route::post('/upload/confirm', [UploadController::class, 'confirmUpload']);
    Route::get('/upload/download/{documentId}', [UploadController::class, 'generateDownloadUrl']);
    Route::delete('/upload/{documentId}', [UploadController::class, 'deleteDocument']);

    // Get documents for specific adoption
    Route::get('/adoptions/{adoptionId}/documents', [UploadController::class, 'getAdoptionDocuments']);
})->middleware(['auth:api', 'check.token.version']);
