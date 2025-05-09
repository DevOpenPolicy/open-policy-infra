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

    private function sendSmsOneTimePassword($phone){
        Otp::where('phone', $phone)->delete();
        $otp = rand(1000, 9999);
        SMS::twilio_send($phone, "Your OTP is $otp. Please use this to verify your account.");

        $otp_data = new Otp();
        $otp_data->phone = $phone;
        $otp_data->otp = $otp;
        $otp_data->expires_at = now()->addMinutes(15);
        $otp_data->save();
        
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
        ]); 
    }

    private function verifySmsOneTimePassword($phone, $code){
        $otp = Otp::where('phone', $phone)
            ->where('otp', $code)
            ->first();

        if(!$otp){
            return response()->json([
                'success' => false,
                'message' => 'OTP is invalid',
            ]);
        }

        if($otp->expires_at < now()){
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired',
            ]);
        }

        $otp->delete();

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
