<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Service\v1\Auth\OneTimePasswordClass;
use Illuminate\Http\Request;

class OneTimePinController extends Controller
{
    private $one_time_password_class;
    public function __construct(){
        $this->one_time_password_class = new OneTimePasswordClass();
    }

    public function sendOtp(Request $request){
        return $this->one_time_password_class->generateOneTimePassword($request);
    }

    public function verifyOtp(Request $request){
        return $this->one_time_password_class->verifyOneTimePassword($request);
    }
}
