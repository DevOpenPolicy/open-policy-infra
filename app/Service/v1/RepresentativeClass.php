<?php

namespace App\Service\v1;

use App\Helper\OpenParliamentClass;
use App\Helper\XmlReaderClass;
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
