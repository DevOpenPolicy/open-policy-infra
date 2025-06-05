<?php

namespace App\Helper;

use App\Models\Politicians;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

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
            return RequestHandlerClass::readHtmlForSummary($url);
        });
    }

    public function getOurCommonsCaInformation($url){
        return Cache::remember($url, $this->setCacheTimer(), function () use ($url) {
            return RequestHandlerClass::findXmlUrlFromCommonsPage($url);
        });
    }

    public function getParliamentConversation($url) {    
        $politicians = Cache::remember('all_politicians_for_search', $this->setCacheTimer(), function () {
            return Politicians::select('id','politician_url')->get();
        });

        return Cache::remember($url, $this->setCacheTimer(), function () use ($url, $politicians) {
            $response = Http::get($url);

            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            $data = [];

            $crawler->filter('.statement_browser')->each(function (Crawler $node) use (&$data, $politicians) {
                $profileAnchor = null;
                $profileImage = null;

                $node->filter('a')->each(function (Crawler $aNode) use (&$profileAnchor, &$profileImage) {
                    if ($aNode->filter('img')->count()) {
                        $profileAnchor = $aNode;
                        $profileImage = $aNode->filter('img')->attr('src');
                    }
                });

                $profileHref = $profileAnchor
                    ? $politicians->where('politician_url', $profileAnchor->attr('href'))->first()?->id
                    : "";

                $statement = $node->filter('.text p')->each(function ($p) {
                    return $p->html();
                });

                $statement = convertAnchorsToReactLinks(implode("<br><br>", $statement));

                // Extract main topic and subtopics
                $mainTopic = "";
                $subTopics = [];

                $mainTopic = $node->filter('.statement_topic')->count() ? $node->filter('.statement_topic')->text() : '';

                $subTopics = [];
                if ($node->filter('.statement_topic')->count()) {
                    $statementParent = $node->filter('.statement_topic')->ancestors()->first();
                    $subTopics = $statementParent->filter('span.br')->each(function ($brNode) {
                        return trim($brNode->getNode(0)->nextSibling->textContent ?? '');
                    });
                }

                $data[] = [
                    'name'         => $node->filter('.pol_name')->count() ? $node->filter('.pol_name')->text() : "",
                    'party'        => $node->filter('.partytag')->count() ? $node->filter('.partytag')->text() : "",
                    'riding'       => $node->filter('.pol_affil')->count() ? $node->filter('.pol_affil')->text() : "",
                    'statement'    => $statement ,
                    'topic'        => $mainTopic,
                    'sub_topics'   => implode(' ', array_filter($subTopics)),
                    'datetime'     => $node->filter('.statement_time_permalink')->count() ? $node->filter('.statement_time_permalink')->text() : "",
                    'profile_url'  => $profileHref ? "/mps/" . $profileHref : "",
                    'image'        => $profileImage ? "https://openparliament.ca" . $profileImage : "",
                ];
            });

            return $data;
        });
    }


}
