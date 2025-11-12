<?php

namespace App\Http\Controllers\v1;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Otp;
use App\Models\User;
use App\Service\v1\Auth\AuthorizationClass;
use App\Service\v1\Auth\OneTimePasswordClass;
use App\SMS;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthorizationController extends Controller
{
    private $authorization_class;
    public function __construct()
    {
        $this->authorization_class = new AuthorizationClass();
    }

    public function login_user(LoginRequest $request){
        return $this->authorization_class->login($request);
    }

    public function register_user(RegisterRequest $request){
        return $this->authorization_class->register($request);
    }

    public function forgot_password(ForgotPasswordRequest $request){
        return $this->authorization_class->forgot_password($request);
    }

    public function googleLogin(Request $request)
{
    $token = $request->input('access_token');

    try {
        $googleUser = Socialite::driver('google')->stateless()->userFromToken($token);

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $googleUser->user['given_name'] ?? '',
                'last_name' => $googleUser->user['family_name'] ?? '',
                'email' => $googleUser->getEmail(),
                'postal_code' => 'M2H2W6', 
                'password' => Hash::make(Str::random(24)),
            ]);
        }
        $authToken = $user->createToken('authorization_token')->plainTextToken;
        return response()->json([
            'success' => true,
            'token' => $authToken,
            'user' => $user,
            'message' => 'Google login successful',
        ]);
    } catch (\Exception $e) {
        logger('Google login error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to authenticate Google user',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function facebookLogin(Request $request)
{
    $token = $request->input('access_token');

    logger('Facebook login token: ' . $token);

    if (!$token) {
        return response()->json([
            'success' => false,
            'message' => 'No access token provided'
        ], 400);
    }

    try {
        $facebookUser = Socialite::driver('facebook')->stateless()->userFromToken($token);
        
        // Log comprehensive Facebook user data for debugging
        logger('=== Facebook User Data ===', [
            'id' => $facebookUser->getId(),
            'nickname' => $facebookUser->getNickname(),
            'name' => $facebookUser->getName(),
            'email' => $facebookUser->getEmail(),
            'avatar' => $facebookUser->getAvatar(),
        ]);
        
        // Log raw data from Facebook
        logger('=== Facebook Raw User Array ===', [
            'user' => $facebookUser->user ?? 'not set',
        ]);
        
        // Log attributes if available
        if (isset($facebookUser->attributes)) {
            logger('=== Facebook User Attributes ===', [
                'attributes' => $facebookUser->attributes,
            ]);
        }
        
        // Log token information
        logger('=== Facebook Token Info ===', [
            'token' => $facebookUser->token ?? 'not set',
            'refreshToken' => $facebookUser->refreshToken ?? 'not set',
            'expiresIn' => $facebookUser->expiresIn ?? 'not set',
        ]);
        
        // Log all available methods/data by accessing the user property directly
        if (property_exists($facebookUser, 'user') && is_array($facebookUser->user)) {
            logger('=== Complete Facebook User Data Structure ===', $facebookUser->user);
        }

        $email = $facebookUser->getEmail();
        
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required but not provided by Facebook. Please ensure your Facebook account has an email and you have granted email permission.',
                'error' => 'Email not available from Facebook user data',
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $facebookUser->user['first_name'] ?? '',
                'last_name' => $facebookUser->user['last_name'] ?? '',
                'email' => $email,
                'postal_code' => 'M2H2W6', 
                'password' => Hash::make(Str::random(24)),
            ]);
        }

        $authToken = $user->createToken('authorization_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $authToken,
            'user' => $user,
            'message' => 'Facebook login successful',
        ]);
    } catch (\Exception $e) {
        logger('Facebook login error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Facebook authentication failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function check_email(Request $request){
        if(!$request->email){
            return response()->json([
                'success' => false,
                'message' => 'No email provided',
                'can_proceed' => false
            ], 400);
        }

        if(User::where('email', $request->email)->exists()){
            return response()->json([
                'success' => false,
                'message' => 'Email already exists',
                'can_proceed' => false
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email is available',
            'can_proceed' => true
        ], 200);
    }

    public function check_forgotten_email(Request $request){
        if(!$request->email && !$request->phone){
            return response()->json([
                'success' => false,
                'message' => 'No email or phone provided',
                'can_proceed' => false
            ], 400);
        }

        
        $otp = rand(1000, 9999);

        $phone = $request->phone;

        if($request->is_email){
            if(!filter_var($request->email, FILTER_VALIDATE_EMAIL)){
                return response()->json([
                    'success' => false,
                    'message' => 'Email is invalid',
                    'can_proceed' => false
                ], 400);
            }

            if(!User::where('email', $request->email)->exists()){
                return response()->json([
                    'success' => false,
                    'message' => 'Email does not exist',
                    'can_proceed' => false
                ], 400);
            }

            $user = User::where('email', $request->email)->first();
            $phone = $user->phone;

            Otp::where('phone', $request->email)->delete();
            $otp_data = new Otp();
            $otp_data->phone = $request->email;
            $otp_data->otp = $otp;
            $otp_data->expires_at = now()->addMinutes(15);
            $otp_data->save();
        }else{
            if(!preg_match('/^\+?[0-9]{10,15}$/', $request->phone)){
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is invalid',
                    'can_proceed' => false
                ], 400);
            }

            if(!User::where('phone', $request->phone)->exists()){
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number does not exist',
                    'can_proceed' => false
                ], 400);
            }

            Otp::where('phone', $request->phone)->delete();
            $otp_data = new Otp();
            $otp_data->phone = $request->phone;
            $otp_data->otp = $otp;
            $otp_data->expires_at = now()->addMinutes(15);
            $otp_data->save();
        }

        
        SMS::twilio_send($phone, "Your OTP is $otp. Please use this to verify your account.");

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'can_proceed' => true
        ], 200);
    }

    public function check_phone_postal(Request $request){
        if(!$request->phone || !$request->postal_code){
            return response()->json([
                'success' => false,
                'message' => 'No phone or postal code provided',
                'can_proceed' => false
            ], 400);
        }

        if(!preg_match('/^\+?[0-9]{10,15}$/', $request->phone)){
            return response()->json([
                'success' => false,
                'message' => 'Phone number is invalid',
                'can_proceed' => false
            ], 400);
        }
        
        $representative = (new OpenParliamentClass)->getPolicyInformation('/search?q=' . $request->postal_code);
        if(!$representative){
            return response()->json([
                'success' => false,
                'message' => 'Postal code is invalid',
                'can_proceed' => false
            ], 400);
        }

        if(User::where('phone', $request->phone)->exists()){
            return response()->json([
                'success' => false,
                'message' => 'Phone number already exists',
                'can_proceed' => false
            ], 400);
        }

        $oneTimePasswordClass = new OneTimePasswordClass();
        return $oneTimePasswordClass->sendSmsOneTimePassword($request->phone);
        
        // Otp::where('phone', $request->phone)->delete();
        // $otp = rand(1000, 9999);
        // SMS::twilio_send($request->phone, "Your OTP is $otp. Please use this to verify your account.");

        // $otp_data = new Otp();
        // $otp_data->phone = $request->phone;
        // $otp_data->otp = $otp;
        // $otp_data->expires_at = now()->addMinutes(15);
        // $otp_data->save();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'OTP sent successfully',
        //     'can_proceed' => true
        // ], 200);
    }
    public function check_phone(Request $request){
        if(!$request->phone){
            return response()->json([
                'success' => false,
                'message' => 'No phone number provided',
                'can_proceed' => false
            ], 400);
        }

        if(!preg_match('/^\+?[0-9]{10,15}$/', $request->phone)){
            return response()->json([
                'success' => false,
                'message' => 'Phone number is invalid',
                'can_proceed' => false
            ], 400);
        }
        
        if(User::where('phone', $request->phone)->exists()){
            return response()->json([
                'success' => false,
                'message' => 'Phone number already exists',
                'can_proceed' => false
            ], 400);
        }

        $oneTimePasswordClass = new OneTimePasswordClass();
        return $oneTimePasswordClass->sendSmsOneTimePassword($request->phone);
    
    }

    public function logout_user(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
