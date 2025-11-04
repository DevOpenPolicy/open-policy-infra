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
        $data = $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code ?? null);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'representative' => $data,
            'message' => 'Logged in successfully'
        ]);
        
    }

    public function register(RegisterRequest $register_request){
        try {
            $data = $register_request->validated();
            
            $userData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'postal_code' => 'M2H2W6',
                'password' => Hash::make($data['password']),
            ];

            if (isset($data['phone']) && !empty($data['phone'])) {
                $userData['phone'] = $data['phone'];
            }

            if (isset($data['postal_code']) && !empty($data['postal_code'])) {
                $userData['postal_code'] = $data['postal_code'];
            }

            $user = User::create($userData);
  

            $token = $user->createToken('authorization_token')->plainTextToken;

            $representativeController = new RepresentativeController();
            $representativeData = $user->postal_code 
                ? $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code)
                : null;

            return response()->json([
                'token' => $token,
                'user' => $user,
                'representative' => $representativeData,
                'success' => true,
                'message' => 'User registered successfully'
            ]);
        } catch (\Exception $e) {
            logger('Registration error: ' . $e->getMessage());
            logger('Error trace: ' . $e->getTraceAsString());

            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
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
        $data = $representativeController->checkRepPostalCodeInformationIsCached($user->postal_code ?? null);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'representative' => $data,
            'success' => true,
            'message' => 'password set successfully'
        ]);
    }
}
