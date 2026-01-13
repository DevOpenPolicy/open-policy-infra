<?php

namespace App\Console\Commands;

use App\Helper\OpenParliamentClass;
use App\Models\Debate;
use Illuminate\Console\Command;

class PopulateDebatesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-debates-table';

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
        // $years = [2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017, 2016, 2015, 2014, 2013, 2012, 2011, 2010, 2009, 2008, 2007, 2006, 2005, 2004, 2003, 2002, 2001, 2000, 1999, 1998, 1997, 1996, 1995, 1994];
        // Get the current year for fetching debates data
        $years = [2025, now()->year];

        // Iterate through each year to fetch debate records
        foreach ($years as $year) {
            // Construct the API endpoint URL for debates in the current year with a limit of 1000 records
            $url = "/debates/$year/?limit=1000";

            logger()->info("Fetching debates for year: {$year} from URL: {$url}");

            // Instantiate the OpenParliamentClass helper to make API calls
            $openParliamentClass = new OpenParliamentClass();

            // Fetch debate data from the Open Parliament API
            // 1. Fetch the data
            $data = $openParliamentClass->getPolicyInformation($url);

            // 2. The Guard Clause: If data is null or objects don't exist, stop here.
            if (is_null($data) || !isset($data['objects'])) {
                logger()->warning("No data found for URL: {$url} with year {$year}. Skipping...");
                continue; // Or 'continue' if this is inside a loop
            }

            // 3. Now it is safe to loop because we know $data['objects'] exists
            foreach ($data['objects'] as $value) {
                // I noticed you added a check for the word, let's keep it clean:
                $word = $value['most_frequent_word']['en'] ?? 'N/A';

                Debate::updateOrCreate(
                    ['debate_url' => $value['url']],
                    [
                        'date' => $value['date'],
                        'number' => $value['number'],
                        'most_frequent_word' => $word,
                        'debate_url' => $value['url'],
                    ],
                );
            }
        }
        // dd('done');
    }
}
