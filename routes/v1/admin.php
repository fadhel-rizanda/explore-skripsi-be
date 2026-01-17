<?php

use App\Http\Controllers\UserController;

Route::prefix('/admin')->middleware(['auth:api', 'check.token.version', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'listUsersAdmin']);
});
