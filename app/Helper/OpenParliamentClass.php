<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

class OpenParliamentClass
{
    private const BASE_URL = 'https://api.openparliament.ca';

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getBaseUrl(){
        return self::BASE_URL;
    }

    public function getRepresentatives(){
        return Cache::remember('open_policy_politicians', now()->addDays(3), function () {
            return RequestHandlerClass::makeRequest(self::BASE_URL.'/politicians');
        });
    }

    public function getRepresentative($url){
        return Cache::remember($url, now()->addDays(3), function () use ($url) {
            return RequestHandlerClass::makeRequest(self::BASE_URL.$url);
        });
    }


}
