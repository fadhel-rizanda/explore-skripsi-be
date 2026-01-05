<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'chats',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::get('/', [ChatController::class, 'getChatRooms']);
    Route::post('/private/{userId}', [ChatController::class, 'getOrCreatePrivateChat']);
    Route::post('/', [ChatController::class, 'createChat']);
    Route::post('/{roomId}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/{roomId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/{roomId}/read', [ChatController::class, 'markRoomAsRead']);
});
