<?php

use \Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'test'], function () {
    Route::get('/auth', function () {
        $user = auth('api')->user();
        return response()->json([
            'error' => false,
            'status' => 'success',
            'message' => 'Authenticated',
            'data' => $user,
        ]);
    })->middleware(['auth:api', 'check.token.version']);
});
