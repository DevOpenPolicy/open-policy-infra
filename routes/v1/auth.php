<?php

use App\Http\Controllers\v1\AuthorizationController;
use App\Http\Controllers\v1\OneTimePinController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('app-auth')->group(function () {
    Route::controller(AuthorizationController::class)->group(function () {
        Route::post('/login', 'login_user');
        Route::post('/register', 'register_user');
        Route::post('/logout', 'logout_user')->middleware(['auth:sanctum']);
    });

    Route::prefix('otp')->controller(OneTimePinController::class)->group(function () {
        Route::post('/send', 'sendOtp');
        Route::post('/verify', 'verifyOtp');
    });

    Route::prefix('forgot')->controller(OneTimePinController::class)->group(function () {
        Route::post('/send', 'sendOtp');
    });
});


Route::prefix('admin-auth')->group(function () {
    Route::controller(AuthorizationController::class)->group(function () {
        Route::post('/login', 'login_user');
        Route::post('/register', 'register_user');
        Route::post('/logout', 'logout_user')->middleware(['auth:sanctum']);
    });

    Route::prefix('otp')->controller(OneTimePinController::class)->group(function () {
        Route::post('/send', 'sendOtp');
        Route::post('/verify', 'verifyOtp');
    });

    Route::prefix('forgot')->controller(OneTimePinController::class)->group(function () {
        Route::post('/send', 'sendOtp');
        Route::post('/verify', 'verifyOtp');
    });
});

Route::get('/user', function (Request $request) {
    return Auth::check();
})->middleware('auth:sanctum');


