<?php

namespace App\Service\v1;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePostalCodeRequest;
use App\Http\Requests\DeleteUserAccountRequest;
use App\Http\Requests\EditUserAccountRequest;
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

        [$votes_cast, $saved_bills, $issues_raised] = Concurrency::run([
            fn() => Cache::remember('votes_cast_'.$user->id, $this->setCacheTimer(), function () {
                return 1;
            }),

            fn() => Cache::remember('saved_bills_'.$user->id, $this->setCacheTimer(), function () {
                return 1;
            }),

            fn() => Cache::remember('issues_raised_'.$user->id, $this->setCacheTimer(), function () {
                return 1;
            }),
        ]);
        

        return [
            'votes_cast' => $votes_cast,
            'saved_bills' => $saved_bills,
            'issues_raised' => $issues_raised
        ];
    }

    public function changeUserPassword(ChangePasswordRequest $change_password_request){
        $user = Auth::user();

        if (!Hash::check($change_password_request->current_password, $user->password)) {
            return response()->json(['message' => 'Invalid current password'], 401);
        }

        User::where('id', $user->id)->update([
            'password' => Hash::make($change_password_request->new_password),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function changePostalCode(ChangePostalCodeRequest $change_postal_code_request){
        $user = Auth::user();

        User::where('id', $user->id)->update([
            'postal_code' => $change_postal_code_request->postal_code,
        ]);

        return response()->json(['message' => 'Postal code changed successfully']);
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
            return response()->json(['message' => 'Invalid email'], 401);
        }

        User::where('id', $user->id)->update([
            'email' => $user->email."_deleted_".now(),
            // 'deleted_at' => now(),
            // 'account_deletion_reason' => $delete_user_account_request->account_deletion_reason,
        ]);

        return response()->json(['message' => 'Account deleted successfully']);
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
