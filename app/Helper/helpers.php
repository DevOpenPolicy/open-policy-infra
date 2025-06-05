<?php

use App\Helper\OpenParliamentClass;
use App\Models\Bill;
use App\Models\BillVoteSummary;
use App\Models\Politicians;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

if (!function_exists('convertAnchorsToReactLinks')) {
    function convertAnchorsToReactLinks($html) {
        return preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i',
            function ($matches) {
                $href = $matches[1];
                $text = $matches[2];
    
                // Lookups
                if (strpos($href, '/politicians/') !== false) {
                    $politician = Politicians::where('politician_url', $href)->first();
                    $href = $politician?->id ? "/mps/" . $politician->id : '#';
                } elseif (strpos($href, '/votes/') !== false) {
                    $href = $href;
                    // $href = '#';
                    // $vote = BillVoteSummary::where('vote_url', $href)->first();
                    // if(!$vote){
                    //     $voteData = (new OpenParliamentClass())->getPolicyInformation($href);
                    //     if ($voteData) {
                    //         $voteStore = BillVoteSummary::create([
                    //             'bill_url' => $voteData['bill_url'] ?? '',
                    //             'session' => $voteData['session'] ?? '',
                    //             'description' => $voteData['description']['en'] ?? '',
                    //             'result' => $voteData['result'] ?? '',
                    //             'vote_url' => $voteData['url'] ?? $href,
                    //             'vote_json' => json_encode($voteData),
                    //         ]);
                    //         $href = $voteStore;
                    //         $href = $voteStore->id ? "/votes/" . $voteStore->id : '#';
                    //     }
                    // }else{
                    //     $href = $vote;
                    //     $href = $vote?->id ? "/votes/" . $vote->id : '#';
                    // }
                    // logger($href);
                } elseif (strpos($href, '/bills/') !== false) {
                    $bill = Bill::where('bill_url', $href)->first();
                    $href = $bill?->id ? "/bills/" . $bill->id : '#';
                }
    
                // Return JSX-style Link
                return '<a href="' . $href . '" className="text-blue-600 hover:underline">' . $text . '</a>';
            },
            $html
        );
    }
    
    
} 
