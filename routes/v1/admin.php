<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/v1')->middleware(['auth:sanctum','admin'])->group(function () {

    Route::prefix('users')->group(function () {
        // list of users

        // search users

        // bulk delete users

        // export users

        // user details

        // show user

        // update user

        // delete user
    });

    Route::prefix('bills')->group(function () {
        // show bills

        // search bills

        // bills details
    });

    Route::prefix('issues')->group(function () {
        // chat with gpt
    });

    Route::prefix('settings')->group(function () {
        // chat with gpt
    });
});

