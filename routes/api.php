<?php

use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:api']]);

Route::group(['prefix' => 'v1'], function () {
    require __DIR__ . '/test-service.php';
    require __DIR__ . '/auth-service.php';
    require __DIR__ . '/attachment-service.php';
    require __DIR__ . '/chat-service.php';
    require __DIR__ . '/notification-service.php';
    require __DIR__ . '/pet-service.php';
});
