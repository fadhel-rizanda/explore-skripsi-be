<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/statuses', [StatusController::class, 'listStatuses']);
Route::get('/tags', [TagController::class, 'listTags']);
Route::get('/roles', [RoleController::class, 'listRoles']);
