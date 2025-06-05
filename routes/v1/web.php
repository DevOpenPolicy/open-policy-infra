<?php

use App\Http\Controllers\Web\BillController;
use App\Http\Controllers\Web\CommitteeController;
use App\Http\Controllers\Web\DebateController;
use App\Http\Controllers\Web\HouseMentionController;
use App\Http\Controllers\Web\PoliticianController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// route for app version 
// route for admin version 
// route for web info

Route::prefix('web')->group(function () {
    Route::prefix('bills')->group(function () {
        Route::get('/', [BillController::class, 'getBills']);
        Route::get('/summary/{id}', [BillController::class, 'getBillSummary']);
        Route::get('/house-mention/{id}', [BillController::class, 'getBillHouseMention']);

    });
    
    Route::prefix('debate')->group(function () {
        
        Route::get('/debate-get-year', [DebateController::class, 'getYearsData']);
        Route::get('/debate-get-year-date', [DebateController::class, 'getYearDate']);
        Route::get('/debate-mentions/{id?}', [DebateController::class, 'getDebateMentions']);
    });
    
    Route::prefix('committee')->group(function () {
        Route::get('/committee-topics', [CommitteeController::class, 'committeeTopics']);
        Route::get('/committee-get-year', [CommitteeController::class, 'getYear']);
        Route::get('/committee-get-year-data/{id}', [CommitteeController::class, 'getYearDate']);
        Route::get('/committee-mentions/{id?}', [CommitteeController::class, 'houseMentions']);
        
    });
    
    // Route::get('/votes', [BillController::class, 'getBills']);
    Route::get('/politician', [PoliticianController::class, 'getPoliticians']);
    Route::get('/former-politician', [PoliticianController::class, 'getFormerPoliticians']);

    Route::get('/get-house-mention', [HouseMentionController::class, 'getHouseMention']);
    Route::get('/get-bill', [HouseMentionController::class, 'getBills']);

    Route::get('/get-votes', [HouseMentionController::class, 'getVotes']);
});


