<?php

namespace App;

use App\Helper\OpenParliamentClass;
use App\Models\Bill;
use App\Models\ParliamentSession;
use App\Models\Politicians;

class GenerateContentClass
{
    public static function generateMP(){
        Politicians::truncate();
        $openParliamentClass = new OpenParliamentClass();
        $politician = $openParliamentClass->getPolicyInformation('/politicians');
        $politicians = $politician['objects'];

        foreach ($politicians as $key => $value) {
            try{
            $data = $openParliamentClass->getPolicyInformation($value['url']);

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
                dd($data, $e->getMessage());
            }
        }
        return Politicians::all();
    }

    public static function generateBill(){
        Bill::truncate();
        ParliamentSession::truncate();
        $openParliamentClass = new OpenParliamentClass();
        $sessionOptions = [
            ['name' => '37th Parliament, 1st Session', 'session' => '37-1'],
            ['name' => '37th Parliament, 2nd Session', 'session' => '37-2'],
            ['name' => '37th Parliament, 3rd Session', 'session' => '37-3'],
            ['name' => '38th Parliament, 1st Session', 'session' => '38-1'],
            ['name' => '39th Parliament, 1st Session', 'session' => '39-1'],
            ['name' => '39th Parliament, 2nd Session', 'session' => '39-2'],
            ['name' => '40th Parliament, 1st Session', 'session' => '40-1'],
            ['name' => '40th Parliament, 2nd Session', 'session' => '40-2'],
            ['name' => '40th Parliament, 3rd Session', 'session' => '40-3'],
            ['name' => '41st Parliament, 1st Session', 'session' => '41-1'],
            ['name' => '41st Parliament, 2nd Session', 'session' => '41-2'],
            ['name' => '42nd Parliament, 1st Session', 'session' => '42-1'],
            ['name' => '43rd Parliament, 1st Session', 'session' => '43-1'],
            ['name' => '43rd Parliament, 2nd Session', 'session' => '43-2'],
            ['name' => '44th Parliament, 1st Session', 'session' => '44-1'],
            ['name' => '45th Parliament, 1st Session', 'session' => '45-1'],
        ];

        foreach ($sessionOptions as $session) {
            ParliamentSession::create($session);

            $value = $session['session'];
            $privateBills =  $openParliamentClass
            ->getPolicyInformation("/bills/?session=$value&private_member_bill=true&limit=1000&offset=0");

            $governmentBills =  $openParliamentClass
            ->getPolicyInformation("/bills/?session=$value&private_member_bill=false&limit=1000&offset=0");

            self::dataFormat($governmentBills['objects'], true);
            self::dataFormat($privateBills['objects'], false);
        }
        
    }

    private static function dataFormat($bills, $is_gov){
        $openParliamentClass = new OpenParliamentClass();
        foreach ($bills as $bill) {
            try{
                $bill_information = $openParliamentClass->getPolicyInformation($bill['url']);
                

            $politician = Politicians::where('politician_url',$bill_information['sponsor_politician_url'])->first();
            if(!$politician){
                $data = $openParliamentClass->getPolicyInformation($bill_information['sponsor_politician_url']);
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
            }

            if(!Bill::where('bill_url',$bill['url'])->first()){
                $bills = new Bill();
                $bills->session = $bill['session'];
                $bills->introduced = $bill['introduced'];
                $bills->name = $bill_information['name']['en'];
                $bills->short_name = $bill_information['short_title']['en'] != "" ? $bill_information['short_title']['en'] : $bills->name;
                $bills->number = $bill['number'];
                $bills->politician = $bill_information['sponsor_politician_url'];
                $bills->bill_url = $bill['url'];
                $bills->is_government_bill = $is_gov;
                $bill['bill_information'] = $bill_information;
                $bills->bills_json = json_encode($bill);
                $bills->save();
            }
            
            }catch(\Exception $e){
                return $e;
            }
        }
    }
}
