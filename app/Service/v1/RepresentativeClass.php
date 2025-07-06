<?php

namespace App\Service\v1;

use App\Helper\OpenParliamentClass;
use App\Helper\XmlReaderClass;
use App\Models\PoliticianActivityLog;
use App\Models\Politicians;
use DOMDocument;
use GuzzleHttp\Psr7\Request;

class RepresentativeClass
{
    private $openParliamentClass;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function getRepresentatives()
    {
        return $this->openParliamentClass->getRepresentatives();
    }

    public function getRepresentativesRole($data)
    {
        return $data['memberships'][0]['label']['en'];
    }

    public function getRepresentativesImage($data)
    {
        return $this->openParliamentClass->getBaseUrl() . $data['image'];
    }

    public function getRepresentative($url)
    {
        return $this->openParliamentClass->getPolicyInformation($url);
    }

    public function getRepresentativeRecentActivities($data)
    {
        $xmlReaderClass = new XmlReaderClass();
        $url = $this->openParliamentClass->getBaseUrl() . $data['related']['activity_rss_url'];
        $formattedXml = $xmlReaderClass->readXml($url);

        return $formattedXml['channel']['item'] ?? [];
    }

    public function getActivityLog(Politicians $politician){
        $recent_activities =  json_decode(PoliticianActivityLog::where('politician_id',$politician->id)->first()?->activity);

            $vote_activity = [];
            $house_activity = [];

            foreach ($recent_activities as $activity => $value) {
                if($value->isTitle == true) continue;

                $html = $value->text;

                libxml_use_internal_errors(true); // suppress warnings

                $dom = new DOMDocument();
                $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

                // Get all <a> elements
                $links = $dom->getElementsByTagName('a');
                $firstHref = $links->length > 0 ? $links->item(0)->getAttribute('href') : null;

                // Replace each <a> tag with its inner text
                foreach (iterator_to_array($links) as $a) {
                    $textNode = $dom->createTextNode($a->nodeValue);
                    $a->parentNode->replaceChild($textNode, $a);
                }

                // Extract cleaned full text
                $body = $dom->getElementsByTagName('body')->item(0);
                $cleanText = trim($body->textContent);

                // dd($value);
                if (strpos($value->title, 'Voted') !== false) {
                    $vote_activity[] = (object) [
                        'info' => $cleanText,
                        'link' => $firstHref,
                    ];
                } else {
                    $house_activity[] = (object) [
                        'info' => $cleanText,
                        'link' => $firstHref,
                    ];
                }
            }

            return [
                'vote_activity' => $vote_activity,
                'house_activity' => $house_activity
            ];
    }

    public function searchRepresentative($name)
    {
        $representatives = $this->getRepresentatives();
        $representatives = $representatives['objects'];
        $representatives = array_filter($representatives, function ($representative) use ($name) {
            return strpos(strtolower($representative['name']), strtolower($name)) !== false;
        });
        return $representatives;
    }

    public function stripHtmlToText(string $html): string
    {
        // Decode HTML entities like &#x27; to apostrophes, &nbsp;, etc.
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip HTML tags
        $stripped = strip_tags($decoded);

        // Optional: Replace multiple spaces/newlines with a single space
        $normalized = preg_replace('/\s+/', ' ', $stripped);

        return trim($normalized);
    }

    public function getRepresentativeAddress($address){
        if (preg_match('/Main office -.*?(?=\n\n|\z)/s', $address, $matches)) {
            $mainOffice = $matches[0];
            return $mainOffice;
        } else {
            return "Main office section not found.";
        }

    }
}
