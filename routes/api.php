<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    require __DIR__ . '/test-service.php';
    require __DIR__ . '/auth-service.php';
    require __DIR__ . '/adoption-service.php';
});
