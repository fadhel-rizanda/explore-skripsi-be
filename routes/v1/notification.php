<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'notifications',
    'middleware' => ['auth:api', 'check.token.version'],
], function () {
    Route::get('/', [NotificationController::class, 'getNotifications']);
    Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/{notification}/mark-as-unread', [NotificationController::class, 'markAsUnread']);
    Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/mark-all-as-unread', [NotificationController::class, 'markAllAsUnread']);
    Route::delete('/{notification}', [NotificationController::class, 'deleteNotification']);
});
