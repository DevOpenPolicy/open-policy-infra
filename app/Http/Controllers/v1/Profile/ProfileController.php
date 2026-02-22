<?php

namespace App\Http\Controllers\v1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePostalCodeRequest;
use App\Http\Requests\DeleteUserAccountRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    private $user_profile_class;
    public function __construct()
    {
        $this->user_profile_class = new \App\Service\v1\UserProfileClass();
    }
    public function changePassword(ChangePasswordRequest $request){
        return $this->user_profile_class->changeUserPassword($request);
    }

    public function changePostalCode(ChangePostalCodeRequest $request){
        return $this->user_profile_class->changePostalCode($request);
    }

    public function editProfile(Request $request){
        $user = User::find(Auth::id());

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'date_of_birth' => 'required|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $user->dp = asset('storage/' . $path);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->gender = $request->gender;
        $user->age = $request->date_of_birth;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);

    }

    public function deleteAccount(DeleteUserAccountRequest $request){
        return $this->user_profile_class->deleteUserAccount($request);
    }

    public function analytics(){
        return $this->user_profile_class->getUseStats();
    }

    public function updateProfilePicture(Request $request){
        $user = User::find(Auth::id());

        $request->validate([
            'profile_pic' => 'required|string',
        ]);

        $base64Image = $request->input('profile_pic');

        if ($base64Image && preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image type'
                ]);
            }

            $image = base64_decode($image);

            if ($image === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base64 decode failed'
                ]);
            }

            $fileName = Str::random(10) . '.' . $type;
            $filePath = 'profiles/' . $fileName;

            Storage::disk('public')->put($filePath, $image);

            $user->dp = asset('storage/' . $filePath);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'user' => $user,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid image format. Please provide a valid base64 encoded image.'
        ]);
    }
}
