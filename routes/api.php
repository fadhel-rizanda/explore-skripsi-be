<?php

use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:api']]);

Route::group(['prefix' => 'v1'], function () {
    require __DIR__ . '/v1/test.php';
    require __DIR__ . '/v1/general.php';
    require __DIR__ . '/v1/auth.php';
    require __DIR__ . '/v1/attachment.php';
    require __DIR__ . '/v1/chat.php';
    require __DIR__ . '/v1/notification.php';
    require __DIR__ . '/v1/pet.php';
    require __DIR__ . '/v1/user.php';
    require __DIR__ . '/v1/community.php';
});
