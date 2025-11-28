<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

    Route::get('/test-s3', function () {
        try {
            // Test write
            Storage::disk('s3')->put('test.txt', 'Hello AWS S3!');

            // Test read
            $content = Storage::disk('s3')->get('test.txt');

            // Test delete
            Storage::disk('s3')->delete('test.txt');

            return 'S3 connection successful! Content: ' . $content;
        } catch (\Exception $e) {
            return 'S3 connection failed: ' . $e->getMessage();
        }
    });
});
