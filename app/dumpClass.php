<?php

namespace App;

use App\Helper\OpenParliamentClass;
use App\Models\Bill;
use App\Models\Politicians;
use App\Service\v1\BillClass;

class dumpClass
{
    private $billClass;
    private $openParliamentClass;
    public function __construct()
    {
        $this->billClass = new BillClass();
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function getAllBills(){
        // $data = $this->billClass->getAllBills();
        // return $this->dataFormat($data['objects']);

        // $politician = $this->openParliamentClass->getPolicyInformation('/politicians');
        // $politicians = $politician['objects'];

        // foreach ($politicians as $key => $value) {
        //     $data = $this->openParliamentClass->getPolicyInformation($value['url']);
        //     // return $data;
        //     $politician_store = new Politicians();
        //     $politician_store->name = $data['name'];
        //     $politician_store->constituency_offices = $data['other_info']['constituency_offices'][0];
        //     $politician_store->email = $data['email'];
        //     $politician_store->voice = $data['voice'];
        //     $politician_store->party_name = $data['memberships'][0]['party']['name']['en'];
        //     $politician_store->party_short_name = $data['memberships'][0]['party']['short_name']['en'];
        //     $politician_store->province_name = $data['memberships'][0]['label']['en'];
        //     $politician_store->province_location = $data['memberships'][0]['riding']['name']['en'];
        //     $politician_store->province_short_name = $data['memberships'][0]['riding']['province'];
        //     $politician_store->politician_url = $data['url'];
        //     $politician_store->politician_image = 'https://openparliament.ca'.$data['image'];
        //     $politician_store->politician_json = json_encode($data);
        //     $politician_store->save();
        // }
        // return Politicians::all();
        // return 'done';

        $privateBills =  $this->openParliamentClass
        ->getPolicyInformation('/bills/?session=44-1&private_member_bill=true&limit=1000&offset=0');

        $governmentBills =  $this->openParliamentClass
        ->getPolicyInformation('/bills/?session=44-1&private_member_bill=false&limit=1000&offset=0');
        // return $governmentBills;

        $this->dataFormat($governmentBills['objects'], true);
        $this->dataFormat($privateBills['objects'], false);

        return 'done';
    }

    private function dataFormat($bills, $is_gov){
        foreach ($bills as $bill) {
            try{
            $bill_information = $this->openParliamentClass->getPolicyInformation($bill['url']);

            return $bill_information['sponsor_politician_url'];
            $politician = Politicians::where('politician_url',$bill_information['sponsor_politician_url'])->first();
            if(!$politician){
                // throw new \Exception('Politician not found: '.$bill_information['sponsor_politician_url']);
                $data = $this->openParliamentClass->getPolicyInformation($bill_information['sponsor_politician_url']);
                // return $data;
                $politician_store = new Politicians();
                $politician_store->name = $data['name'];
                $politician_store->constituency_offices = $data['other_info']['constituency_offices'][0];
                $politician_store->email = $data['email'];
                $politician_store->voice = $data['voice'];
                $politician_store->party_name = $data['memberships'][0]['party']['name']['en'];
                $politician_store->party_short_name = $data['memberships'][0]['party']['short_name']['en'];
                $politician_store->province_name = $data['memberships'][0]['label']['en'];
                $politician_store->province_location = $data['memberships'][0]['riding']['name']['en'];
                $politician_store->province_short_name = $data['memberships'][0]['riding']['province'];
                $politician_store->politician_url = $data['url'];
                $politician_store->politician_image = 'https://openparliament.ca'.$data['image'];
                $politician_store->politician_json = json_encode($data);
                $politician_store->save();

                $politician = Politicians::where('politician_url',$bill_information['sponsor_politician_url'])->first();
            }
            $bills = new Bill();
            $bills->session = $bill['session'];
            $bills->introduced = $bill['introduced'];
            $bills->name = $bill_information['name']['en'];
            $bills->short_name = $bill_information['short_title']['en'] != "" ? $bill_information['short_title']['en'] : $bills->name;
            $bills->number = $bill['number'];
            $bills->politician = $bill_information['sponsor_politician_url'];
            $bills->bill_url = $bill['url'];
            $bills->is_government_bill = $is_gov;
            $bills->bills_json = json_encode($bill);
            $bills->save();
            }catch(\Exception $e){
                // return $bill;
                return $e;
            }
        }
    }   

    function lookuplater(){
        // // return $this->openParliamentClass->getPolicyInformation('/bills/?limit=1000&offset=0&session=41-1');
        // $privateBills =  $this->openParliamentClass
        // ->getPolicyInformation('/bills/?session=44-1&private_member_bill=true&limit=1000&offset=0');

        // $governmentBills =  $this->openParliamentClass
        // ->getPolicyInformation('/bills/?session=44-1&private_member_bill=false&limit=1000&offset=0');

        // $this->dataFormat($governmentBills['objects']);
        // $this->dataFormat($privateBills['objects']);
        // return [$privateBills, $governmentBills];
    }
}
