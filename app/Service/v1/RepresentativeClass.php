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

    public function getRepresentatives(){
        return $this->openParliamentClass->getRepresentatives();
    }

    public function getRepresentativesRole($data){
        return $data['current_party']['short_name']['en']." MP for ".$data['current_riding']['name']['en'];
    }

    public function getRepresentativesImage($data){
        return $this->openParliamentClass->getBaseUrl().$data['image'];
    }

    public function getRepresentative($url){
        return $this->openParliamentClass->getPolicyInformation($url);
    }

    public function getRepresentativeRecentActivities($data){
        $xmlReaderClass = new XmlReaderClass();
        $url = $this->openParliamentClass->getBaseUrl().$data['related']['activity_rss_url'];
        $formattedXml = $xmlReaderClass->readXml($url);

        return $formattedXml['channel']['item'];
    }

    public function searchRepresentative($name){
        $representatives = $this->getRepresentatives();
        $representatives = $representatives['objects'];
        $representatives = array_filter($representatives, function($representative) use ($name) {
            return strpos(strtolower($representative['name']), strtolower($name)) !== false;
        });
        return $representatives;
    }
}
