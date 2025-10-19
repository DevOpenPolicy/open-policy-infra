<?php

namespace App\Console\Commands;

use App\Helper\OpenParliamentClass;
use App\Models\Politicians;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckIsFormerMp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-is-former-mp'; // Run with: php artisan app:check-is-former-mp

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update politicians who are former MPs'; 

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize helper class for Open Parliament API
        $openParliamentClass = new OpenParliamentClass();

        // API endpoint (includes both active and former MPs, max 500 per page)
        $url = '/politicians/?include=former&limit=500';

        // Keep fetching until there are no more pages
        do {
            // Fetch data from API
            $politicians = $openParliamentClass->getPolicyInformation($url);

            // If we got valid results
            if (!empty($politicians['objects']) && is_array($politicians['objects'])) {
                // Extract just the URLs we need for updating
                $politicianUrls = array_column($politicians['objects'], 'url');

                if (!empty($politicianUrls)) {
                    // PERFORMANCE FIX ✅
                    // Instead of updating one row at a time (N queries), 
                    // do a bulk update in a single query using WHERE IN.
                    Politicians::whereIn('politician_url', $politicianUrls)
                        ->update(['is_former' => true]);
                }
            }

            // Move to next page if available
            $url = $politicians['pagination']['next_url'] ?? null;

            // Small delay to avoid hammering API (optional safeguard)
            usleep(200000); // 0.2 seconds

        } while (!empty($url)); // Continue until no more pages
    }
}
