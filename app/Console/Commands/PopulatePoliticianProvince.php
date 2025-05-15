<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provinces = [
            ['Alberta', 'AB'],
            ['British Columbia', 'BC'],
            ['Manitoba', 'MB'],
            ['New Brunswick', 'NB'],
            ['Newfoundland and Labrador', 'NL'],
            ['Northwest Territories', 'NT'],
            ['Nova Scotia', 'NS'],
            ['Nunavut', 'NU'],
            ['Ontario', 'ON'],
            ['Prince Edward Island', 'PE'],
            ['Quebec', 'QC'],
            ['Saskatchewan', 'SK'],
            ['Yukon', 'YT']
        ];

        foreach ($provinces as $province) {
            \App\Models\PoliticianProvince::create([
                'name' => $province[0],
                'short_name' => $province[1]
            ]);
        }

        $this->info('Politician provinces populated successfully.');
    }
}
