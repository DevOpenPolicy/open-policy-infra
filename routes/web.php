<?php

use App\GenerateContentClass;
use App\Helper\OpenParliamentClass;
use App\Http\Controllers\DeveloperController;
use App\Jobs\SetupSystem;
use App\Jobs\SystemSetUp;
use App\Models\Bill;
use App\Models\BillVoteSummary;
use App\Models\Committee;
use App\Models\CommitteeYearLog;
use App\Models\CommitteeYearLogData;
use App\Models\Debate;
use App\Models\ParliamentSession;
use App\Models\PoliticianActivityLog;
use App\Models\Politicians;
use App\Models\User;
use App\Service\v1\BillClass;
use App\Service\v1\CommitteeClass;
use App\Service\v1\DebateClass;
use App\Service\v1\RepresentativeClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Symfony\Component\DomCrawler\Crawler;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/counts', function () {
    $activirttt =  json_decode(PoliticianActivityLog::first()->activity);

    $vote_activity = [];
    $house_activity = [];

    foreach ($activirttt as $activity => $value) {
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
    dd($vote_activity, $house_activity);
});




