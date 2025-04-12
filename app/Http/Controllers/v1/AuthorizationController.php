<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Service\v1\Auth\AuthorizationClass;
use Illuminate\Http\Request;

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

    public function logout_user(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
