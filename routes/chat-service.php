<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'chats',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::get('/rooms', [ChatController::class, 'getChatRooms']);
    Route::post('/private/{userId}', [ChatController::class, 'getOrCreatePrivateChat']);
    Route::post('/groups', [ChatController::class, 'createChat']);
    Route::post('/rooms/{roomId}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/rooms/{roomId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/rooms/{roomId}/read', [ChatController::class, 'markRoomAsRead']);
});
