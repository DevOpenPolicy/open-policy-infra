<?php

use App\Http\Controllers\v1\AppLinkController;
use App\Http\Controllers\v1\Bills\BillController;
use App\Http\Controllers\v1\Bills\CommentController;
use App\Http\Controllers\v1\Chat\ChatController;
use App\Http\Controllers\v1\Issue\RepresentativeIssueController;
use App\Http\Controllers\v1\MP\RepresentativeController;
use App\Http\Controllers\v1\NotificationController;
use App\Http\Controllers\v1\Profile\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('app/v1')->group(function () {
    Route::prefix('representatives')->group(function () {
        // get your MP
        Route::get('/', [RepresentativeController::class, 'getUserRepresentative'])->middleware(['auth:sanctum']);

        // search MP's
        Route::get('/all', [RepresentativeController::class, 'showRepresentatives']);

        // MP profile
        Route::get('/single', [RepresentativeController::class, 'getRepresentative']);

        // view issue
        Route::get('/view-issue', [RepresentativeController::class, 'getIssue'])->middleware(['auth:sanctum']);

        Route::get('/guest-view-issue', [RepresentativeController::class, 'getIssue']);

        // support the issue
        Route::post('/support-issue', [RepresentativeController::class, 'supportIssue'])->middleware(['auth:sanctum']);

        //bookmark
        Route::post('/bookmark-issue', [RepresentativeController::class, 'bookmarkIssue'])->middleware(['auth:sanctum']);

        //activity links
        Route::get('/activity-link', [AppLinkController::class, 'activityLink']);
    });


    Route::prefix('bills')->group(function () {
        // get all bills
        Route::get('/', [BillController::class, 'getAllBills']);

        Route::get('/user-bill', [BillController::class, 'userBills'])->middleware(['auth:sanctum']);
        
        // bill details
        Route::get('/show/{number}', [BillController::class, 'getBillNumber'])->middleware(['auth:sanctum']);

        Route::get('/guest-show/{number}', [BillController::class, 'getBillNumber']);

        // support bill
        Route::post('/support', [BillController::class, 'supportBill'])->middleware(['auth:sanctum']);

        //bookmark bill
        Route::post('/bookmark', [BillController::class, 'bookmarkBill'])->middleware(['auth:sanctum']);

        // Comments routes
        Route::prefix('comments')->group(function () {
            // Get comments (public)
            Route::get('/{billId}', [CommentController::class, 'getComments']);
            
            // Create comment (authenticated)
            Route::post('/', [CommentController::class, 'createComment'])->middleware(['auth:sanctum']);
            
            // Update comment (authenticated)
            Route::put('/{commentId}', [CommentController::class, 'updateComment'])->middleware(['auth:sanctum']);
            
            // Delete comment (authenticated)
            Route::delete('/{commentId}', [CommentController::class, 'deleteComment'])->middleware(['auth:sanctum']);
            
            // React to comment (authenticated)
            Route::post('/{commentId}/react', [CommentController::class, 'reactToComment'])->middleware(['auth:sanctum']);
        });
    });

    Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/get-bill', [ChatController::class, 'getBillInformation']);
        Route::get('/get-issue', [ChatController::class, 'getIssueInformation']);
        Route::post('/bill-chat', [ChatController::class, 'billChat']);
        Route::post('/bill-chat-link', [ChatController::class, 'billChatLink']);
        Route::post('/issue-chat', [ChatController::class, 'issueChat']);
    });

    Route::prefix('link')->group(function () {
        Route::get('/debate', [AppLinkController::class, 'debateActivityLink']);
        Route::get('/committee', [AppLinkController::class, 'committeeActivityLink']);
    });

    Route::prefix('profile')->middleware(['auth:sanctum'])->group(function () {
        // show users bill information 
        Route::get('/', [ProfileController::class, 'analytics']);

        // change password
        Route::post('/change-password', [ProfileController::class, 'changePassword']);

        // change postal code
        Route::post('/postal-code', [ProfileController::class, 'changePostalCode']);

        // edit profile
        Route::post('/update-profile', [ProfileController::class, 'editProfile']);

        // delete account
        Route::post('/delete-account', [ProfileController::class, 'deleteAccount']);
    });

    
    Route::prefix('issue')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // create issue
        Route::post('/create', [RepresentativeIssueController::class, 'createIssue']);
        
        // delete issue
        Route::post('/delete', [RepresentativeIssueController::class, 'requestDeletion']);
        
    });

    Route::prefix('notifications')->middleware(['auth:sanctum'])->group(function () {
        // update user's push token
        Route::post('/update-token', [NotificationController::class, 'updatePushToken']);
        
        // send notification to authenticated user
        Route::post('/send-to-me', [NotificationController::class, 'sendToMe']);
        
        // send notifications to multiple users by IDs
        Route::post('/send-to-users', [NotificationController::class, 'sendToUsers']);
        
        // send push notifications to custom recipients
        Route::post('/send', [NotificationController::class, 'sendPushNotifications']);
    });
});


