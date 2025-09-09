<?php

namespace App\Console\Commands;

use App\Helper\OpenParliamentClass;
use App\Models\Politicians;
use Illuminate\Console\Command;

class CheckIsFormerMp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-is-former-mp';

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
        $openParliamentClass = new OpenParliamentClass();
        // $politicians = $openParliamentClass->getPolicyInformation('/politicians/?include=former&limit=500');
        // $politicians = $politicians['objects'];

        // foreach ($politicians as $politician) {
        //     Politicians::where('politician_url', $politician['url'])->update([
        //         'is_former' => true
        //     ]);
        // }

        // $openParliamentClass = new OpenParliamentClass();
        // $politicians = $openParliamentClass->getPolicyInformation('/politicians/?include=former&limit=500&offset=500');
        // $politicians = $politicians['objects'];

        // foreach ($politicians as $politician) {
        //     Politicians::where('politician_url', $politician['url'])->update([
        //         'is_former' => true
        //     ]);
        // }

        $allPoliticians = [];
        $url = '/politicians/?include=former&limit=500';

        do {
            $politicians = $openParliamentClass->getPolicyInformation($url);

            if (isset($politicians['objects']) && is_array($politicians['objects'])) {
                $allPoliticians = array_merge($allPoliticians, $politicians['objects']);
            }

            $url = $politicians['pagination']['next_url'] ?? null;
        } while (!empty($url));

        foreach ($allPoliticians as $politician) {
            Politicians::where('politician_url', $politician['url'])->update([
                'is_former' => true
            ]);
        }

        // $this->comment('Politician provinces populated successfully.');
    }
}
