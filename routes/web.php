<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'error' => false,
        'status' => 'success',
        'message' => 'Welcome to the API',
        'data' => ['version' => '1.0']
    ], 200);
});
