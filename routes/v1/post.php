<?php

use App\Enums\ModelReferenceEnum;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'posts',
    'middleware' => ['throttle:read'],
], function () {
    Route::get('/', [PostController::class, 'listPosts']);

    Route::middleware(['model.isActive:' . ModelReferenceEnum::POST->value])->group(function () {
        Route::get('/{post}', [PostController::class, 'postDetail']);
        Route::get('/{post}/comments', [CommentController::class, 'listComments']);
        Route::get('/{post}/comments/{comment}', [CommentController::class, 'listReplies'])->scopeBindings();
    });

    Route::middleware(['auth:api', 'check.token.version'])->group(function () {

        Route::middleware(['throttle:write'])->group(function () {
            Route::post('/', [PostController::class, 'createPost']);

            Route::middleware(['model.isActive:' . ModelReferenceEnum::POST->value])->group(function () {
                Route::post('/{post}/comments', [CommentController::class, 'createComment']);
                Route::post('/{post}/likes', [PostController::class, 'likePost']);
                Route::delete('/{post}/comments/{comment}', [CommentController::class, 'deleteComment'])
                    ->middleware('comment.owner');

                Route::middleware('post.owner')->group(function () {
                    Route::put('/{post}', [PostController::class, 'updatePost']);
                    Route::delete('/{post}', [PostController::class, 'deletePost']);
                });

                Route::middleware('role:admin')->group(function () {
                    Route::post('/{post}/takedown', [ModerationController::class, 'takeDownPost']);
                    Route::post('/{post}/restore', [ModerationController::class, 'restorePost']);
                });
            });
        });
    });
});
