<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'chats',
    'middleware' => ['auth:api', 'check.token.version', 'throttle:read'],
], function () {
    Route::get('/', [ChatController::class, 'getChatRooms']);

    Route::middleware(['throttle:write'])->group(function () {
        Route::post('/', [ChatController::class, 'createChat']);
    });

    Route::middleware(['chat.member'])->group(function () {
        Route::get('/{chat}/messages', [ChatController::class, 'getMessages']);

        Route::middleware(['throttle:write'])->group(function () {
            Route::post('/{chat}/messages', [ChatController::class, 'sendMessage']);
            Route::patch('/{chat}/read', [ChatController::class, 'markRoomAsRead']);
            Route::delete('/{chat}/chat', [ChatController::class, 'deleteChat']);
            Route::delete('/{chat}/messages/{message}', [ChatController::class, 'deleteMessage']);
            Route::put('/{chat}/messages/{message}', [ChatController::class, 'updateMessage']);
            Route::delete('/{chat}/leave', [ChatController::class, 'leaveChat']);
            Route::delete('/{chat}/members/{user}', [ChatController::class, 'removeUserFromChat']);
        });
    });
});
