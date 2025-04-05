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

    private function setCacheTimer(){
        return now()->addDays(7);
    } 

    public function getRepresentatives(){
        return Cache::remember('open_policy_politicians', $this->setCacheTimer(), function () {
            return RequestHandlerClass::makeRequest(self::BASE_URL.'/politicians');
        });
    }

    public function getPolicyInformation($url){
        return Cache::remember($url, $this->setCacheTimer(), function () use ($url) {
            return RequestHandlerClass::makeRequest(self::BASE_URL.$url);
        });
    }

    public function getParlCaInformation($url){
        return Cache::remember($url, $this->setCacheTimer(), function () use ($url) {
            return RequestHandlerClass::findXmlUrlFromParlPage($url);
        });
    }


}
