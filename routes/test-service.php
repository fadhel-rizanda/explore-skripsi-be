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

    Route::get('/role', function () {
        $user = auth('api')->user();
        if ($user->hasRole('ADMIN')) {
            return response()->json([
                'error' => false,
                'status' => 'success',
                'message' => 'User has admin role',
            ]);
        } else {
            return response()->json([
                'error' => true,
                'status' => 'forbidden',
                'message' => 'User does not have admin role',
            ], 403);
        }
    })->middleware(['auth:api', 'check.token.version', 'role:ADMIN']);
});
