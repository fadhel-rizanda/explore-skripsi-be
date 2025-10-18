<?php

use \Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'test'], function () {
    Route::get('/auth', function () {
        return response()->json('Authenticated access');
    })->middleware('auth:api');
});
