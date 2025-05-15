<?php

namespace App\Console\Commands;

use App\Helper\OpenParliamentClass;
use App\Models\Committee;
use App\Models\CommitteeYearLog;
use App\Models\CommitteeYearLogData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class setUpCommitee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-up-committee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Committee::truncate();
        CommitteeYearLog::truncate();
        CommitteeYearLogData::truncate();

        $data = ((new OpenParliamentClass()))->getPolicyInformation('/committees/?limit=1000');

        foreach ($data['objects'] as $value) {
            $committee = new \App\Models\Committee();
            $committee->name = $value['name']['en'];
            $committee->short_name = $value['short_name']['en'];
            $committee->slug = $value['slug'];
            $committee->parent_url = $value['parent_url'];
            $committee->committee_url = $value['url'];
            $committee->save();
        }

        foreach (Committee::all() as $value) {
            $response = Http::get("https://openparliament.ca/committees/$value->slug");
    
            if (!$response->successful()) {
                return [];
            }
    
            $html = $response->body();
            $crawler = new Crawler($html);
    
            // Get all .row elements and select the 3rd one (index 2)
            $thirdRow = $crawler->filter('.content div .row')->eq(2);
    
            // From that row, find all anchor tags within .column-block
            $links = $thirdRow->filter('.column-block a');
    
            // Extract href and year
            $data = $links->each(function (Crawler $node) {
                $text = $node->text();
                preg_match('/\b(20\d{2})\b/', $text, $matches);
                return [
                    'year' => $matches[1] ?? null,
                    'url' => $node->attr('href'),
                ];
            });
    
            foreach ($data as $d) {
                $committeeYearLog = new CommitteeYearLog();
                $committeeYearLog->committee_id = $value->id;
                $committeeYearLog->year = $d['year'];
                $committeeYearLog->url = $d['url'];
                $committeeYearLog->save();
            }
            
        }

        foreach(CommitteeYearLog::all() as $value){
            $response = Http::get("https://openparliament.ca$value->url");
            if (!$response->successful()) {
                return [];
            }
    
            $html = $response->body();
    
            $crawler = new Crawler($html);
        
            // Get all .row elements and select the 3rd one (index 2)
            $thirdRow = $crawler->filter('.content div .row')->eq(0);
    
            // From that row, find all anchor tags within .column-block
            $links = $thirdRow->filter('.column.column-block')->each(function (Crawler $node) {
                // Check if the div does not have the 'no_evidence' class
                $classes = $node->attr('class');
                if (strpos($classes, 'no_evidence') === false) {
                    return $node->filter('a')->each(function (Crawler $anchor) {
                        $text = $anchor->text(); // e.g., "Dec. 17, 2024"
                        preg_match('/\b(20\d{2})\b/', $text, $matches);
                        return [
                            'date' => $text,
                            'url' => $anchor->attr('href'),
                        ];
                    });
                }
            });
            
            $links = array_filter($links, function ($link) {
                return $link !== null;
            });
    
            foreach ($links as $link) {
                $committeeYearLogData = new \App\Models\CommitteeYearLogData();
                $committeeYearLogData->committee_year_log_id = $value->id;
                $committeeYearLogData->date = $link[0]['date'];
                $committeeYearLogData->url = $link[0]['url'];
                $committeeYearLogData->save();
            }
        }

        
    }
}
