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
        $years = [2024,2023,2022, 2021, 2020, 2019, 2018, 2017, 2016, 2015, 2014, 2013, 2012,
            2011, 2010, 2009, 2008,2007, 2006, 2005, 2004, 2003, 2002, 2001, 2000, 1999,1998,1997,1996, 1995,1994
        ];

        foreach ($years as $year) {
            $url = "/debates/$year/?limit=1000";
            $data = ((new OpenParliamentClass())->getPolicyInformation($url));

            foreach ($data['objects'] as $value) {
                $debate = new Debate();
                $debate->date = $value['date'];
                $debate->number = $value['number'];
                $debate->most_frequent_word = $value['most_frequent_word']['en'];
                $debate->debate_url = $value['url'];
                $debate->save();
            }

        }
        dd('done');

        

    }
}
