<?php

namespace App\Service\v1\Auth;

class OneTimePasswordClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function sendSmsOneTimePassword($phone){
        // cache by user phone_number
    }

    public function verifySmsOneTimePassword($phone, $code){

    }

    public function sendEmailOneTimePassword($email){

    }

    public function verifyEmailOneTimePassword($email, $code){

    }
}
