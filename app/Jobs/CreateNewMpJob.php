<?php

namespace App\Jobs;

use App\Helper\OpenParliamentClass;
use App\Models\Politicians;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateNewMpJob implements ShouldQueue
{
    use Queueable;
    private $url;
    /**
     * Create a new job instance.
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $data = (new OpenParliamentClass())->getPolicyInformation($this->url);

            $politician_store = new Politicians();
            $politician_store->name = $data['name'];
            $politician_store->constituency_offices = $data['other_info']['constituency_offices'][0] ?? '';
            $politician_store->email = $data['email'] ?? '';
            $politician_store->voice = $data['voice'] ?? '';
            $politician_store->party_name = $data['memberships'][0]['party']['name']['en'] ?? '';
            $politician_store->party_short_name = $data['memberships'][0]['party']['short_name']['en'] ?? ''; 
            $politician_store->province_name = $data['memberships'][0]['label']['en'] ?? '';
            $politician_store->province_location = $data['memberships'][0]['riding']['name']['en'] ?? '';
            $politician_store->province_short_name = $data['memberships'][0]['riding']['province'] ?? '';
            $politician_store->politician_url = $data['url'];
            $politician_store->politician_image = 'https://openparliament.ca'.$data['image'];
            $politician_store->politician_json = json_encode($data);
            $politician_store->save();
        } catch(\Exception $e){
            logger($e->getMessage());
        }
    }
}
