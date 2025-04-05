<?php

namespace App\Service\v1\Auth;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::find(Auth::user()->id);
        $token = $user->createToken('authorization_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
        
    }

    public function register(RegisterRequest $register_request){

        $data = $register_request->validated();

        $user = User::create([
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'phone' => $data->phone,
            'postal_code' => $data->postal_code,
            'password' => Hash::make($data->password),
        ]);


        $token = $user->createToken('authorization_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
}
