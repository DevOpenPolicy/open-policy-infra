<?php

use App\Http\Controllers\v1\Admin\AdminBilController;
use App\Http\Controllers\v1\Admin\AdminIssueController;
use App\Http\Controllers\v1\Admin\AdminUserController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/v1')
    ->middleware(['auth:sanctum', AdminMiddleware::class])
    ->group(function () {

    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'getUsers']);
        Route::post('/', [AdminUserController::class, 'createUser']);
        Route::get('/{id}', [AdminUserController::class, 'getUser']);
        Route::put('/{id}', [AdminUserController::class, 'updateUser']);
        Route::delete('/{id}', [AdminUserController::class, 'deleteUser']);

        Route::post('/logout', [AdminUserController::class, 'logout']);
    });

    Route::prefix('bills')->group(function () {
        Route::get('/', [AdminBilController::class, 'getBills']);
        Route::get('/{id}', [AdminBilController::class, 'getBill']);
    });

    Route::prefix('issues')->group(function () {
        Route::get('/', [AdminIssueController::class, 'getIssues']);
        Route::get('/{id}', [AdminIssueController::class, 'getIssue']);
        Route::put('/{id}', [AdminIssueController::class, 'updateIssues']);
    });

    Route::prefix('settings')->group(function () {
        Route::get('/', [AdminBilController::class, 'getBills']);
    });

    Route::get('/profile', [AdminUserController::class, 'account']);
    Route::post('/profile', [AdminUserController::class, 'account_update']);
});

