<?php

namespace App\Helper;

use Illuminate\Support\Facades\Http;

class RequestHandlerClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function makeRequest($url, $method = 'GET', $data = [], $headers = [])
    {
        $response = Http::withHeaders($headers)->send($method, $url, [
            'json' => $data,
        ]);

        return $response->json();
    }
}
