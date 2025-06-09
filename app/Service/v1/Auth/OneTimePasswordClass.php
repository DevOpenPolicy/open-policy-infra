<?php

namespace App\Service\v1\Auth;

use App\Models\Otp;
use App\SMS;
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

    public function sendSmsOneTimePassword($phone){
        $data = SMS::twilio_send('+1'.$phone);
        
        if(!$data){
            return response()->json([
                'success' => false,
                'message' =>  'Error Sending OTP, try again',
            ]); 
        }
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
        ]); 
    }

    private function verifySmsOneTimePassword($phone, $code){
        $data = SMS::twilio_verify('+1'.$phone, $code);

        if(!$data){
            return response()->json([
                'success' => false,
                'message' =>  'Wrong OTP',
            ]); 
        }
        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);

    }

    private function sendEmailOneTimePassword($email){

    }

    private function verifyEmailOneTimePassword($email, $code){

    }
}
