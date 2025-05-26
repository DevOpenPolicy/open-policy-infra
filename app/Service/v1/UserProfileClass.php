<?php

namespace App\Service\v1;

use App\Http\Controllers\v1\MP\RepresentativeController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePostalCodeRequest;
use App\Http\Requests\DeleteUserAccountRequest;
use App\Http\Requests\EditUserAccountRequest;
use App\Models\BillVoteCast;
use App\Models\RepresentativeIssue;
use App\Models\SavedBill;
use App\Models\SavedIssue;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Hash;

class UserProfileClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function setCacheTimer(){
        return now()->addDays(7);
    }

    public function getUseStats(){
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'votes_cast' => BillVoteCast::where('user_id', $user->id)->count(),
            'saved_bills' =>  SavedBill::where('user_id', $user->id)->where('is_saved',1)->count(),
            'issues_raised' => RepresentativeIssue::where('representative_id', $user->id)->where('status','approved')->count()
        ]);
    }

    public function changeUserPassword(ChangePasswordRequest $change_password_request){
        $user = Auth::user();

        if (!Hash::check($change_password_request->old_password, $user->password)) {
            return response()->json(['message' => 'Invalid current password'], 401);
        }

        User::where('id', $user->id)->update([
            'password' => Hash::make($change_password_request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    public function changePostalCode(ChangePostalCodeRequest $change_postal_code_request){
        $user = Auth::user();

        User::where('id', $user->id)->update([
            'postal_code' => $change_postal_code_request->postal_code,
        ]);

        $representativeController = new RepresentativeController();
        $data = $representativeController->checkRepPostalCodeInformationIsCached($change_postal_code_request->postal_code);


        return response()->json([
            'success' => true,
            'message' => 'Postal code changed successfully',
            'user' => Auth::user(),
            'data' => $data
        ]);
    }

    public function accountDeletionReasons(){
        $data = [
            (object)[
                'label' => "I no longer need the app",
                'value' => "I no longer need the app"
            ],
            (object)[
                'label' => "I found a better alternative",
                'value' => "I found a better alternative"
            ],
            (object)[
                'label' => "I have privacy or security concerns",
                'value' => "I have privacy or security concerns"
            ],
            (object)[
                'label' => 'The app does not meet my expectations',
                'value' => 'The app does not meet my expectations'
            ],
            (object)[
                'label' => 'Other',
                'value' => 'Other'
            ]
        ];

        return response()->json($data);
    }

    public function deleteUserAccount(DeleteUserAccountRequest $delete_user_account_request){
        $user = Auth::user();

        if($user->email !== $delete_user_account_request->email){
            return response()->json([
                'success' => false,
                'message' => 'Invalid email'
            ], 401);
        }

        User::where('id', $user->id)->update([
            'email' => $user->email."_deleted_".now(),
            'deleted_at' => now(),
            'account_deletion_reason' => $delete_user_account_request->account_deletion_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    public function editProfile(EditUserAccountRequest $edit_user_account_request){
        $user = Auth::user();

        // ! add the part for profile picture 

        User::where('id', $user->id)->update([
            'first_name' => $edit_user_account_request->first_name,
            'last_name' => $edit_user_account_request->last_name,
            'gender' => $edit_user_account_request->gender,
            'age' => $edit_user_account_request->age,
        ]);

        return response()->json(['message' => 'Profile updated successfully']);
    }


}
