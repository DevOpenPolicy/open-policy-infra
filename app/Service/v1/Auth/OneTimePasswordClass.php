<?php

namespace App\Service\v1\Auth;

use Illuminate\Http\Request;

class OneTimePasswordClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function generateOneTimePassword(Request $request){
        $platform = strtolower($request->platform ?? 'sms');
        if($platform == 'sms'){
            return $this->sendSmsOneTimePassword($request->phone);
        }elseif($platform == 'email'){
            return $this->sendEmailOneTimePassword($request->email);
        }
    }

    public function verifyOneTimePassword(Request $request){
        $platform = strtolower($request->platform ?? 'sms');
        if($platform == 'sms'){
            return $this->verifySmsOneTimePassword($request->phone, $request->code);
        }elseif($platform == 'email'){
            return $this->verifyEmailOneTimePassword($request->email, $request->code);
        }
    }

    private function sendSmsOneTimePassword($phone){
        $otp = 2000;
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
        ]); 
    }

    private function verifySmsOneTimePassword($phone, $code){
        if($code == 2000){
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
            ]);
        }

        
        return response()->json([
            'success' => false,
            'message' => 'OTP is invalid',
        ]);

    }

    private function sendEmailOneTimePassword($email){

    }

    private function verifyEmailOneTimePassword($email, $code){

    }
}
