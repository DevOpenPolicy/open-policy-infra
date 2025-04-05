<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

class XmlReaderClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function readXml($url){
        return Cache::remember($url, now()->addDays(3), function () use ($url) {
            $xml = simplexml_load_file($url);
            $json = json_encode($xml);
            $array = json_decode($json,TRUE);
            return $array;
        });
    }
}
