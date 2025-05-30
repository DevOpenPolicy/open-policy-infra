<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Politicians;
use App\Models\User;
use App\RoleManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function getUsers(){
        $users = User::where('role','!=', RoleManager::ADMIN)
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('gender', 'like', "%{$search}%")
                      ->orWhere('postal_code', 'like', "%{$search}%");
                });
            })
            ->paginate(10);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => Number::abbreviate(User::where('role','!=', RoleManager::ADMIN)->count())
        ]);
    }

    public function getUser($id){
        $user = User::where('id', $id)->first();
        // $user->age = (int)Carbon::parse($user->age)->diffInYears(now());
        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function deleteUser($id){
        $user = User::find($id);
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        $user->tokens()->delete();
        $user->email = $user->email."_deleted";
        $user->phone = $user->phone."_deleted";
        $user->deleted_at = now();
        $user->account_deletion_reason = "Account deleted by admin";
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User account deleted successful'
        ]);
    }

    public function updateUser($id,Request $request){
        $user = User::find($id);
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        if($request->role == RoleManager::REPRESENTATIVE){
            $name = $request->first_name." ".$request->last_name;
            $politician = Politicians::where('name' ,$name )
                ->first();
                
                if(!$politician){
                    return response()->json([
                        'success' => false,
                        'message' => 'No link found with any MP account, update name to match MP'
                    ]);
                }
        }

        if($request->profile_pic && ($request->profile_pic != $user->dp)){
            $base64Image = $request->input('profile_pic');

            if ($base64Image && preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image typed'
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
            }
        }

        if(isset($request->new_password) && $request->new_password == $request->confirm_password){
            $user->password = Hash::make($request->new_password);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->gender = $request->gender;
        $user->email = $user->email;
        $user->age = $request->age;
        $user->phone = $request->phone;
        $user->postal_code = $request->postal_code;
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User information updated successfully'
        ]);
    }

    public function createUser(Request $request){
        $image = NULL;
        if($request->role == RoleManager::REPRESENTATIVE){
            $name = $request->first_name." ".$request->last_name;
            $politician = Politicians::where('name' ,$name )
                ->first();
                
                if(!$politician){
                    return response()->json([
                        'success' => false,
                        'message' => 'No link found with any MP account, update name to match MP'
                    ]);
                }
        }

        $base64Image = $request->input('profile_pic');

        if ($base64Image && preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image typed'
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

            $image = asset('storage/' . $filePath);
        }

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->gender = $request->gender;
        $user->age = $request->age;
        $user->phone = $request->phone;
        $user->postal_code = $request->postal_code;
        $user->password = Hash::make($request->new_password);
        $user->dp = $image;
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User created successfully'
        ]);
    }

    public function account(){
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully'
        ]);
    }

    public function account_update(Request $request){
        $user = User::find(Auth::user()->id);
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        if($request->profile_pic && ($request->profile_pic != $user->dp)){
            $base64Image = $request->input('profile_pic');

            if ($base64Image && preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image typed'
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
            }
        }

        if(isset($request->new_password) && $request->new_password == $request->confirm_password){
            $user->password = Hash::make($request->new_password);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->gender = $request->gender;
        $user->email = $user->email;
        $user->age = $request->age;
        $user->phone = $request->phone;
        $user->postal_code = $request->postal_code;
        $user->save();

        return response()->json([
            'success' => true,
            'user'=>$user->only(['first_name','last_name','dp','phone','postal_code','role']),
            'message' => 'User information updated successfully'
        ]);
    }

}
