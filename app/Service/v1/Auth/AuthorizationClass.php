<?php

namespace App\Service\v1\Auth;

use App\Http\Controllers\v1\MP\RepresentativeController;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthorizationClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function login(LoginRequest $login_request){
        if (!Auth::attempt($login_request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 402);
        }

        $user = User::find(Auth::user()->id);
        $token = $user->createToken('authorization_token')->plainTextToken;

        $representativeController = new RepresentativeController();
        $data = $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'representative' => $data,
            'message' => 'Logged in successfully'
        ]);
        
    }

    public function register(RegisterRequest $register_request){

        $data = $register_request->validated();
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'postal_code' => $data['postal_code'],
            'password' => Hash::make($data['password']),
            'phone_verified_at' => now(),
        ]);

        $token = $user->createToken('authorization_token')->plainTextToken;

        $representativeController = new RepresentativeController();
        $data = $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'representative' => $data,
            'success' => true,
            'message' => 'User registered successfully'
        ]);
    }

    public function forgot_password(ForgotPasswordRequest $forgot_password_request){
        $user = User::where('email', $forgot_password_request->user)
            ->orWhere('phone', $forgot_password_request->user)
            ->first();

        $user->update([
            'password' => Hash::make($forgot_password_request->password)
        ]);

        $token = $user->createToken('authorization_token')->plainTextToken;

        $representativeController = new RepresentativeController();
        $data = $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'representative' => $data,
            'success' => true,
            'message' => 'password set successfully'
        ]);
    }
}
