<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// auth 

//! sign up 
//! log in
//! forgot password flow



// representatives

// parliaments 

// user profile 
