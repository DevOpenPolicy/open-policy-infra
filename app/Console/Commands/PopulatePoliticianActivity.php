<?php

namespace App\Console\Commands;

use App\Models\PoliticianActivityLog;
use App\Models\Politicians;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Console command to populate politician activity logs by scraping data from openparliament.ca
 */
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
     * Fetches politician activity data from openparliament.ca and stores it in the database.
     */
    public function handle()
    {
        // Process politicians in chunks of 100 to manage memory usage
        Politicians::chunk(100, function ($politicians) {
            foreach ($politicians as $politician) {
                while (true) {
                    try {
                        // Fetch HTML content from the politician's openparliament page
                        $response = Http::get("https://openparliament.ca{$politician->politician_url}");
                        // $response = cache()->remember("job_politician_activity_{$politician->id}", 14400, function () use ($politician) {
                        // });

                        // Return early if the HTTP request fails
                        if (!$response->successful()) {
                            return [];
                        }

                        // Extract the HTML body from the response
                        $html = $response->body();

                        // Initialize a DOM crawler to parse HTML content
                        $crawler = new Crawler($html);

                        // Extract election summary from the first paragraph
                        $firstPara = $crawler->filter('.main-col > p')->first()->text();
                        preg_match('/Won (?:his|her|their) last election, in (\d{4}), with (\d+)% of the vote\./', $firstPara, $matches);
                        $electionSummary = $matches ? "Won his last election, in {$matches[1]}, with {$matches[2]}% of the vote." : null;

                        // Initialize activity array to store parsed activity items
                        $activity = [];

                        // Check if activity section exists on the page
                        if ($crawler->filter('#activity')->count() === 0) {
                            // If no activity section found, save empty activity logs
                            PoliticianActivityLog::updateOrCreate(
                                ['politician_id' => $politician->id],
                                [
                                    'election_summary' => $electionSummary,
                                    'activity' => json_encode([]),
                                    'latest_activity' => json_encode([]),
                                ],
                            );

                            logger()->info("Successfully processed activity for politician Name {$politician->name}");
                            break;
                        }

                        // Get all child elements within the activity section
                        $activityCrawler = $crawler->filter('#activity')->children();

                        // Parse each element in the activity section
                        $activityCrawler->each(function (Crawler $node) use (&$activity) {
                            $tag = $node->nodeName();

                            // Handle section titles (h3 tags)
                            if ($tag === 'h3') {
                                $activity[] = [
                                    'title' => trim($node->text()),
                                    'text' => null,
                                    'subtitle' => null,
                                    'isTitle' => true,
                                ];
                            }

                            // Handle activity items (p tags with activity_item class)
                            if ($tag === 'p' && $node->attr('class') === 'activity_item') {
                                // Extract tag information if present
                                $title = $node->filter('.tag')->count() ? trim($node->filter('.tag')->text()) : null;
                                // Extract excerpt/subtitle if present
                                $subtitle = $node->filter('.excerpt')->count() ? trim($node->filter('.excerpt')->text()) : null;

                                // Extract full text while removing the tag span element
                                $nodeHtml = $node->html();
                                $textOnly = trim(preg_replace('/<span class="tag.*?<\/span>/', '', $nodeHtml));

                                $activity[] = [
                                    'title' => $title,
                                    'text' => $textOnly,
                                    'subtitle' => $subtitle,
                                    'isTitle' => false,
                                ];
                            }
                        });

                        // Store or update the politician's activity log in the database
                        PoliticianActivityLog::updateOrCreate(
                            ['politician_id' => $politician->id],
                            [
                                'election_summary' => $electionSummary,
                                'activity' => json_encode($activity),
                                // Store only the latest 2 non-title activity items
                                'latest_activity' => json_encode(
                                    array_slice(
                                        array_filter($activity, function ($item) {
                                            return !$item['isTitle'];
                                        }),
                                        0,
                                        2,
                                    ),
                                ),
                            ],
                        );

                        logger()->info("Successfully processed activity for politician Name {$politician->name}");

                        break; // Exit the retry loop on success
                    } catch (\Exception $e) {
                        // If we hit a cURL timeout/connection error, retry with exponential backoff.
                        if (str_contains($e->getMessage(), 'cURL error')) {
                            static $retryCount = 0;
                            $retryCount++;

                            if ($retryCount > 1) {
                                $retryCount = 1; // Cap retries to avoid excessively long waits
                            }

                            $sleepTime = 60 * pow(2, $retryCount - 1);
                            logger()->error("Error processing politician Name {$politician->name}: " . $e->getMessage());

                            // Progress bar countdown
                            for ($i = 0; $i < $sleepTime; $i++) {
                                $remaining = $sleepTime - $i;
                                $percent = ($i / $sleepTime) * 100;
                                echo "\rRetrying in {$remaining}s [" . str_repeat('=', (int) ($percent / 5)) . str_repeat(' ', 20 - (int) ($percent / 5)) . "] {$percent}%";
                                sleep(1);
                            }
                            echo "\n";
                            continue;
                        }
                        // Reset retry count and log non-retryable exceptions.
                        $retryCount = 0;

                        logger($e);
                        break;
                    }
                }

                sleep(3);
            }
        });
    }
}
