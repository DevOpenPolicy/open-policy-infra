<?php

namespace App\Console\Commands;

use App\Models\PoliticianActivityLog;
use App\Models\Politicians;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class PopulatePoliticianActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-politician-activity';

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
        // PoliticianActivityLog::truncate();
        Politicians::chunk(100, function ($politicians) {
            foreach ($politicians as $politician) {
                try{
                $response = Http::get("https://openparliament.ca{$politician->politician_url}");
        
                if (!$response->successful()) {
                    return [];
                }

                $html = $response->body();

                $crawler = new Crawler($html);

                // 1. Extract election summary
                $firstPara = $crawler->filter('.main-col > p')->first()->text();
                preg_match('/Won (?:his|her|their) last election, in (\d{4}), with (\d+)% of the vote\./', $firstPara, $matches);
                $electionSummary = $matches ? "Won his last election, in {$matches[1]}, with {$matches[2]}% of the vote." : null;

                $activity = [];
                if($crawler->filter('#activity')->count() === 0){
                    PoliticianActivityLog::updateOrCreate(
                        ['politician_id' => $politician->id],
                        [
                            'election_summary' => $electionSummary,
                            'activity' => json_encode([]),
                            'latest_activity' => json_encode([]),
                        ]
                    );

                    continue;
                }
                $activityCrawler = $crawler->filter('#activity')->children();

                $activityCrawler->each(function (Crawler $node) use (&$activity) {
                    $tag = $node->nodeName();

                    if ($tag === 'h3') {
                        $activity[] = [
                            'title' => trim($node->text()),
                            'text' => null,
                            'subtitle' => null,
                            'isTitle' => true,
                        ];
                    }

                    if ($tag === 'p' && $node->attr('class') === 'activity_item') {
                        $title = $node->filter('.tag')->count() ? trim($node->filter('.tag')->text()) : null;
                        $subtitle = $node->filter('.excerpt')->count() ? trim($node->filter('.excerpt')->text()) : null;

                        // Extract full text minus the <span> tag
                        $nodeHtml = $node->html();
                        $textOnly = trim(
                            preg_replace('/<span class="tag.*?<\/span>/', '', $nodeHtml) // remove the tag
                        );
                        // $textOnly = strip_tags($textOnly); // strip remaining HTML

                        $activity[] = [
                            'title' => $title,
                            'text' => $textOnly,
                            'subtitle' => $subtitle,
                            'isTitle' => false,
                        ];
                    }
                });

                PoliticianActivityLog::updateOrCreate(
                    ['politician_id' => $politician->id],
                    [
                        'election_summary' => $electionSummary,
                        'activity' => json_encode($activity),
                        'latest_activity' => json_encode(array_slice(array_filter($activity, function ($item) {
                            return !$item['isTitle'];
                        }), 0, 2)),
                    ]
                );
                }catch(\Exception $e){
                    $this->error("Error processing politician ID {$politician->id}: " . $e->getMessage());
                }
            }
        });
        $this->info('Politician activity populated successfully.');
    }
}
