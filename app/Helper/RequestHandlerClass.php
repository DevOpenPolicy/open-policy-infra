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

        foreach ($xpath->query("//a[@data-download='xml']") as $node) {
            if ($node instanceof \DOMElement) {
                $href = $node->getAttribute('href');
                return strpos($href, 'http') === 0 ? $href : 'https://www.parl.ca' . $href;
            }
        }
        return null;
    }

    public static function findXmlUrlFromCommonsPage($url){
        $html = file_get_contents($url);
        if (!$html) return null;

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query("//a[contains(text(), 'XML')]") as $node) {
            if ($node instanceof \DOMElement) {
                $href = $node->getAttribute('href');
                return strpos($href, 'http') === 0 ? $href : 'https://www.ourcommons.ca/' . $href;
            }
        }
        return null;
    }

    public static function readHtmlForSummary($url){
        $url = 'https://openparliament.ca'.$url;

        $html = file_get_contents($url);
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML warnings
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Use XPath to get the .details div
        $xpath = new DOMXPath($dom);
        $detailsNodeList = $xpath->query("//div[@class='bill_summary']");

        if ($detailsNodeList->length > 0) {
            $summaryNode = $detailsNodeList->item(0);

            // Convert the single node to HTML
            $innerHTML = '';
            foreach ($summaryNode->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }

            // Replace <br> with newlines and strip tags
            $textWithLineBreaks = str_replace(['<br>', '<br/>', '<br />'], "\n", $innerHTML);
            $cleanText = trim(strip_tags($textWithLineBreaks));

            return $cleanText;
        } else {
            return "No summary found.";
        }

    }
}
