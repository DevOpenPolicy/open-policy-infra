<?php

namespace App\Helper;

use DOMDocument;
use DOMXPath;
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

    public static function findXmlUrlFromParlPage($url){
        $html = file_get_contents($url);
        if (!$html) return null;

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query("//a[contains(text(), 'XML')]") as $node) {
            if ($node instanceof \DOMElement) {
                $href = $node->getAttribute('href');
                return strpos($href, 'http') === 0 ? $href : 'https://www.parl.ca' . $href;
            }
        }
        return null;
    }
}
