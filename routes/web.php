<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'OpenPolicy API is running successfully',
        'status' => 'active',
        'timestamp' => now()->toISOString()
    ]);
});





