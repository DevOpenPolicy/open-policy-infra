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
    protected $signature = 'app:set-up-committee';
    protected $description = 'Command description';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2000; // milliseconds

    public function handle()
    {
        // Step 1: Fetch committees with retry

        $retry = 0;
        do {
            try {
                $data = (new OpenParliamentClass())->getPolicyInformation('/committees/?limit=1000');
                foreach ($data['objects'] as $value) {
                    Committee::updateOrCreate(
                        ['slug' => $value['slug']],
                        [
                            'name' => $value['name']['en'] ?? '',
                            'short_name' => $value['short_name']['en'] ?? '',
                            'slug' => $value['slug'] ?? '',
                            'parent_url' => $value['parent_url'] ?? '',
                            'committee_url' => $value['url'] ?? '',
                        ],
                    );
                }
                break;
            } catch (\Exception $e) {
                $this->error("Error fetching committees: {$e->getMessage()}");
                $retry++;
                if ($retry < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY * 1000);
                }
            }
        } while ($retry < self::MAX_RETRIES);

        // Step 2: Scrape committee pages with retry
        foreach (Committee::all() as $value) {
            $retry = 0;
            do {
                try {
                    $response = Http::get("https://openparliament.ca/committees/$value->slug");
                    if (!$response->successful()) {
                        throw new \Exception('Unsuccessful response');
                    }

                    $html = $response->body();
                    $crawler = new Crawler($html);
                    $thirdRow = $crawler->filter('.content div .row')->eq(2);
                    $links = $thirdRow->filter('.column-block a');

                    $data = $links->each(function (Crawler $node) {
                        $text = $node->text();
                        preg_match('/\b(20\d{2})\b/', $text, $matches);
                        return [
                            'year' => $matches[1] ?? null,
                            'url' => $node->attr('href'),
                        ];
                    });

                    foreach ($data as $d) {
                        CommitteeYearLog::updateOrCreate(
                            ['committee_id' => $value->id],
                            [
                                'year' => $d['year'],
                                'url' => $d['url'],
                                'committee_id' => $value->id
                            ],
                        );
                    }
                    break;
                } catch (\Exception $e) {
                    $this->error("Error processing committee {$value->slug}: {$e->getMessage()}");
                    $retry++;
                    if ($retry < self::MAX_RETRIES) {
                        usleep(self::RETRY_DELAY * 1000);
                    }
                }
            } while ($retry < self::MAX_RETRIES);
        }

        // Step 3: Scrape yearly log pages with retry
        
        foreach (CommitteeYearLog::all() as $value) {
            $retry = 0;
            do {
                try {
                    $response = Http::get("https://openparliament.ca$value->url");

                    logger()->info("Fetching committee year log from URL: https://openparliament.ca$value->url");
                    if (!$response->successful()) {
                        throw new \Exception('Unsuccessful response');
                    }

                    $html = $response->body();
                    $crawler = new Crawler($html);
                    $thirdRow = $crawler->filter('.content div .row')->eq(0);

                    $links = $thirdRow->filter('.column.column-block')->each(function (Crawler $node) {
                        $classes = $node->attr('class');
                        if (strpos($classes, 'no_evidence') === false) {
                            return $node->filter('a')->each(function (Crawler $anchor) {
                                $text = $anchor->text();
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
                        CommitteeYearLogData::updateOrCreate(
                            ['committee_year_log_id' => $value->id],
                            [
                                'date' => $link[0]['date'],
                                'url' => $link[0]['url'],
                                'committee_year_log_id' => $value->id
                            ],
                        );
                    }

                    logger()->info("Successfully processed yearly log {$value->id}");
                    break;
                } catch (\Exception $e) {
                    logger()->error("Error processing yearly log {$value->id}: {$e->getMessage()}");
                    $retry++;
                    if ($retry < self::MAX_RETRIES) {
                        usleep(self::RETRY_DELAY * 1000);
                    }
                }
            } while ($retry < self::MAX_RETRIES);
        }
    }
}
