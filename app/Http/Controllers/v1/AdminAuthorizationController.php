<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthorizationController extends Controller
{
    public function adminLogin(Request $request){
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 200);
        }

        $user = User::find(Auth::user()->id);
        if($user->role != 490){
            $user->tokens()->delete();
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $user->createToken('authorization_token')->plainTextToken,
                'user' => $user->only(['first_name','last_name','dp','phone','postal_code','role'])
            ]
        ], 200);
    }

    public function sendOtp(Request $request){
        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 200);
        }

        $code = rand(1111, 9999);
        Otp::create([
            'phone' => $user->email,
            'otp' => $code,
            'expires_at' => now()->addMinutes(15)
        ]);

        try {
            Mail::raw("This is your otp code \n $code", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Forgot Password');
            });
        } catch (\Exception $e) {
            logger($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending mail'
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Otp sent successfully'
        ], 200);
    }

    public function verifyOtp(Request $request){
        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 200);
        }


        $otp = Otp::where('phone', $user->email)
              ->where('otp', $request->otp)
              ->where('expires_at', '>', now())
              ->first();

        if (!$otp) {
            return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP'
            ], 200);
        }

        $otp->delete();
        return response()->json([
            'success' => true,
            'message' => 'Otp verified successfully'
        ], 200);
    }

    public function newPassword(Request $request){
        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 200);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'user password updated successfully'
        ], 200);
    }

    
}
