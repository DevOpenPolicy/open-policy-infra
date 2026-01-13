<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Console command to populate the politician_province table with Canadian provinces.
 * This command creates or updates province records in the database.
 */
class PopulatePoliticianProvince extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-politician-province';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the politician_province table with Canadian provinces and territories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define array of Canadian provinces and territories with full names and abbreviations
        $provinces = [['Alberta', 'AB'], ['British Columbia', 'BC'], ['Manitoba', 'MB'], ['New Brunswick', 'NB'], ['Newfoundland and Labrador', 'NL'], ['Northwest Territories', 'NT'], ['Nova Scotia', 'NS'], ['Nunavut', 'NU'], ['Ontario', 'ON'], ['Prince Edward Island', 'PE'], ['Quebec', 'QC'], ['Saskatchewan', 'SK'], ['Yukon', 'YT']];

        // Iterate through each province and create or update database records
        foreach ($provinces as $province) {
            \App\Models\PoliticianProvince::updateOrCreate(
                ['short_name' => $province[1]], // lookup condition - match by abbreviation
                ['name' => $province[0], 'short_name' => $province[1]], // attributes to create/update
            );
        }
    }
}
