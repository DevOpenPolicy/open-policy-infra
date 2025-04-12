<?php

use App\Http\Controllers\v1\Bills\BillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('app/v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('representatives')->group(function () {
        // get your MP

        // search MP's

        // MP profile

        //MP issues

        // support the issue 

        // do not support the issue
    });


    Route::prefix('bills')->group(function () {
        // get all bills
        Route::get('/', [BillController::class, 'getAllBills']);
        
        // bill details
        Route::get('/show/{number}', [BillController::class, 'getBillNumber']);

        // support bill
        Route::post('/support', [BillController::class, 'supportBill']);

        //bookmark bill
        Route::post('/bookmark', [BillController::class, 'bookmarkBill']);
    });

    Route::prefix('gpt-chat')->group(function () {
        // chat with gpt
    });

    Route::prefix('profile')->group(function () {
        // show users bill information 

        // change password

        // edit profile

        // delete account 

        
    });

    Route::prefix('issue')
    // ->middleware(['representative'])
    ->group(function () {
        // create issue
        // delete issue
    });
});


