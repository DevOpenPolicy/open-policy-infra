<?php

namespace App;

use App\Helper\OpenParliamentClass;
use App\Models\Bill;
use App\Models\ParliamentSession;
use App\Models\Politicians;

class GenerateContentClass
{
    public static function generateMP()
    {
        $openParliamentClass = new OpenParliamentClass();

        $allPoliticians = [];
        $url = '/politicians';

        do {
            $politicians = $openParliamentClass->getPolicyInformation($url);

            if (isset($politicians['objects']) && is_array($politicians['objects'])) {
                $allPoliticians = array_merge($allPoliticians, $politicians['objects']);
            }

            $url = $politicians['pagination']['next_url'] ?? null;
        } while (!empty($url));

        foreach ($allPoliticians as $value) {
            try {
                $data = $openParliamentClass->getPolicyInformation($value['url']);

                Politicians::updateOrCreate(
                    ['politician_url' => $data['url']],
                    [
                        'name' => $data['name'],
                        'constituency_offices' => $data['other_info']['constituency_offices'][0] ?? '',
                        'email' => $data['email'] ?? '',
                        'voice' => $data['voice'] ?? '',
                        'party_name' => $data['memberships'][0]['party']['name']['en'] ?? '',
                        'party_short_name' => $data['memberships'][0]['party']['short_name']['en'] ?? '',
                        'province_name' => $data['memberships'][0]['label']['en'] ?? '',
                        'province_location' => $data['memberships'][0]['riding']['name']['en'] ?? '',
                        'province_short_name' => $data['memberships'][0]['riding']['province'] ?? '',
                        'politician_image' => 'https://openparliament.ca' . $data['image'],
                        'politician_url' => $data['url'],
                        'politician_json' => json_encode($data),
                    ],
                );
            } catch (\Exception $e) {
                logger($e);
                logger($data ?? '');
                // throw new \Exception($e->getMessage());
            }
        }
    }

    public static function generateBill()
    {
        $openParliamentClass = new OpenParliamentClass();
        // add or remove sessions 
        $sessionOptions = [
            // ['name' => '37th Parliament, 1st Session', 'session' => '37-1'],
            // ['name' => '37th Parliament, 2nd Session', 'session' => '37-2'],
            // ['name' => '37th Parliament, 3rd Session', 'session' => '37-3'],
            // ['name' => '38th Parliament, 1st Session', 'session' => '38-1'],
            // ['name' => '39th Parliament, 1st Session', 'session' => '39-1'],
            // ['name' => '39th Parliament, 2nd Session', 'session' => '39-2'],
            // ['name' => '40th Parliament, 1st Session', 'session' => '40-1'],
            // ['name' => '40th Parliament, 2nd Session', 'session' => '40-2'],
            // ['name' => '40th Parliament, 3rd Session', 'session' => '40-3'],
            // ['name' => '41st Parliament, 1st Session', 'session' => '41-1'],
            // ['name' => '41st Parliament, 2nd Session', 'session' => '41-2'],
            // ['name' => '42nd Parliament, 1st Session', 'session' => '42-1'],
            // ['name' => '43rd Parliament, 1st Session', 'session' => '43-1'],
            // ['name' => '43rd Parliament, 2nd Session', 'session' => '43-2'],
            // ['name' => '44th Parliament, 1st Session', 'session' => '44-1'],
            ['name' => '45th Parliament, 1st Session', 'session' => '45-1'],
        ];

        foreach ($sessionOptions as $session) {
            ParliamentSession::updateOrCreate(['session' => $session['session']], ['name' => $session['name']]);

            $value = $session['session'];
            $url = "/bills/?session=$value&private_member_bill=true&limit=1000&offset=0";
            $allPrivateBills = [];

            do {
                $privateBills = $openParliamentClass->getPolicyInformation($url);

                if (isset($privateBills['objects']) && is_array($privateBills['objects'])) {
                    $allPrivateBills = array_merge($allPrivateBills, $privateBills['objects']);
                }

                $url = $privateBills['pagination']['next_url'] ?? null;
            } while (!empty($url));

            self::dataFormat($allPrivateBills, false);

            $url = "/bills/?session=$value&private_member_bill=false&limit=1000&offset=0";
            $allGovernmentBills = [];

            do {
                $governmentBills = $openParliamentClass->getPolicyInformation($url);

                if (isset($governmentBills['objects']) && is_array($governmentBills['objects'])) {
                    $allGovernmentBills = array_merge($allGovernmentBills, $governmentBills['objects']);
                }

                $url = $governmentBills['pagination']['next_url'] ?? null;
            } while (!empty($url));

            self::dataFormat($allGovernmentBills, true);
        }
    }

    private static function dataFormat($bills, $is_gov)
    {
        $openParliamentClass = new OpenParliamentClass();
        foreach ($bills as $bill) {
            while (true) {
                try {
                    $bill_information = $openParliamentClass->getPolicyInformation($bill['url']);
                    logger([$bill['session'], $bill['number'], 'started']);
                    $data = null;
                    if (!empty($bill_information['sponsor_politician_url'])) {
                        $data = $openParliamentClass->getPolicyInformation($bill_information['sponsor_politician_url']);
                    }

                    if (!empty($bill_information['sponsor_politician_url'])) {
                        Politicians::updateOrCreate(
                            ['politician_url' => $bill_information['sponsor_politician_url']],
                            [
                                'name' => $data['name'] ?? '',
                                'constituency_offices' => $data['other_info']['constituency_offices'][0] ?? '',
                                'email' => $data['email'] ?? '',
                                'voice' => $data['voice'] ?? '',
                                'party_name' => $data['memberships'][0]['party']['name']['en'] ?? '',
                                'party_short_name' => $data['memberships'][0]['party']['short_name']['en'] ?? '',
                                'province_name' => $data['memberships'][0]['label']['en'] ?? '',
                                'province_location' => $data['memberships'][0]['riding']['name']['en'] ?? '',
                                'province_short_name' => $data['memberships'][0]['riding']['province'] ?? '',
                                'politician_image' => isset($data['image']) ? 'https://openparliament.ca' . $data['image'] : '',
                                'politician_url' => $bill_information['sponsor_politician_url'],
                                'politician_json' => json_encode($data),
                            ],
                        );
                    }

                    Bill::updateOrCreate(
                        ['bill_url' => $bill['url']],
                        [
                            'session' => $bill['session'],
                            'introduced' => $bill['introduced'],
                            'name' => $bill_information['name']['en'],
                            'short_name' => !empty($bill_information['short_title']['en']) ? $bill_information['short_title']['en'] : $bill_information['name']['en'],
                            'number' => $bill['number'],
                            'politician' => $bill_information['sponsor_politician_url'] ?? 'government',
                            'is_government_bill' => $is_gov,
                            'bill_url' => $bill['url'],
                            'bills_json' => json_encode(array_merge($bill, ['bill_information' => $bill_information])),
                        ],
                    );
                    logger([$bill['session'], $bill['number'], 'ended']);

                    break;
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'cURL error')) {
                        logger()->warning("Timeout fetching {$bill['url']}, retrying in 60s...");
                        sleep(60); 
                        continue; 
                    }

                    logger($e);
                    break;
                }
            }
        }
    }
}
