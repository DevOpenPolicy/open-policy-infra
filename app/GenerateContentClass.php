<?php

namespace App;

// Helper that wraps requests to the OpenParliament API and returns parsed responses.
use App\Helper\OpenParliamentClass;

// Eloquent models used to persist fetched data.
use App\Models\Bill;
use App\Models\ParliamentSession;
use App\Models\Politicians;

/**
 * GenerateContentClass
 *
 * Responsible for fetching data from OpenParliament and saving it to local models.
 * - generateMP(): fetches all politicians and persists them.
 * - generateBill(): fetches bills by sessions and persists both bills and sponsor politicians.
 * - dataFormat(): helper to normalize and store bill details.
 */
class GenerateContentClass
{
    /**
     * Fetch all Members of Parliament (MPs) from the OpenParliament API and persist them.
     *
     * Flow:
     * 1. Instantiate OpenParliamentClass to perform API calls.
     * 2. Page through /politicians endpoint and collect all politician summaries.
     * 3. For each politician summary, fetch the full politician data (detail URL).
     * 4. Retry on transient cURL errors with exponential backoff.
     * 5. Upsert politician data into the Politicians model.
     */
    public static function generateMP()
    {
        // API helper instance used to perform HTTP requests to the OpenParliament API.
        $openParliamentClass = new OpenParliamentClass();

        // Accumulate all politician summary objects returned by the paginated endpoint.
        $allPoliticians = [];
        $url = '/politicians';

        // Paginate through the list of politicians, merging results into $allPoliticians.
        do {
            $politicians = $openParliamentClass->getPolicyInformation($url);

            // Ensure we only merge when 'objects' exists and is an array.
            if (isset($politicians['objects']) && is_array($politicians['objects'])) {
                $allPoliticians = array_merge($allPoliticians, $politicians['objects']);
            }

            // Move to next page if the API returned a next_url.
            $url = $politicians['pagination']['next_url'] ?? null;
        } while (!empty($url));

        // Iterate over each politician summary and fetch the detailed politician page.
        foreach ($allPoliticians as $value) {
            while (true) {
                try {
                    // Fetch detailed data for a single politician.
                    $data = $openParliamentClass->getPolicyInformation($value['url']);
                    logger('Processing MP: ' . $data['name']);

                    // Upsert politician information into the database.
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
                            // Build absolute image URL if an image path exists.
                            'politician_image' => 'https://openparliament.ca' . $data['image'],
                            'politician_url' => $data['url'],
                            'politician_json' => json_encode($data),
                        ],
                    );

                    // Success: break the retry loop and continue with next politician.
                    break;
                } catch (\Exception $e) {
                    // If we hit a cURL timeout/connection error, retry with exponential backoff.
                    if (str_contains($e->getMessage(), 'cURL error')) {
                        static $retryCount = 0;
                        $retryCount++;

                        if ($retryCount > 3) {
                            $retryCount = 1; // Cap retries to avoid excessively long waits
                        }

                        $sleepTime = 60 * pow(2, $retryCount - 1);
                        logger()->warning("Timeout fetching {$data['name']}, retrying in {$sleepTime}s...");

                        // Progress bar countdown
                        for ($i = 0; $i < $sleepTime; $i++) {
                            $remaining = $sleepTime - $i;
                            $percent = ($i / $sleepTime) * 100;
                            echo "\rRetrying in {$remaining}s [" . str_repeat('=', (int) ($percent / 5)) . str_repeat(' ', 20 - (int) ($percent / 5)) . "] {$percent}%";
                            sleep(1);
                        }
                        echo "\n";
                        continue;
                    }
                    // Reset retry count and log non-retryable exceptions.
                    $retryCount = 0;

                    logger($e);
                    break;
                }
            }
        }
    }

    /**
     * Fetch bills for configured parliamentary sessions and persist them.
     *
     * - sessionOptions: configure which sessions to fetch.
     * - For each session:
     *   - Persist session metadata.
     *   - Fetch private member bills and government bills (separately).
     *   - Normalize and persist bills via dataFormat().
     */
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
            // Ensure the ParliamentSession exists for this session identifier.
            ParliamentSession::updateOrCreate(['session' => $session['session']], ['name' => $session['name']]);

            $value = $session['session'];
            // Fetch all private member bills for this session (paginate).
            $url = "/bills/?session=$value&private_member_bill=true&limit=1000&offset=0";
            $allPrivateBills = [];

            do {
                // echo $url . "\n";
                $privateBills = $openParliamentClass->getPolicyInformation($url);

                if (isset($privateBills['objects']) && is_array($privateBills['objects'])) {
                    $allPrivateBills = array_merge($allPrivateBills, $privateBills['objects']);
                }

                $url = $privateBills['pagination']['next_url'] ?? null;
            } while (!empty($url));

            // Normalize and persist private member bills (is_gov = false).
            self::dataFormat($allPrivateBills, false);

            // Fetch all government bills for this session (paginate).
            $url = "/bills/?session=$value&private_member_bill=false&limit=1000&offset=0";
            $allGovernmentBills = [];

            do {
                // echo $url . "\n";
                $governmentBills = $openParliamentClass->getPolicyInformation($url);

                if (isset($governmentBills['objects']) && is_array($governmentBills['objects'])) {
                    $allGovernmentBills = array_merge($allGovernmentBills, $governmentBills['objects']);
                }

                $url = $governmentBills['pagination']['next_url'] ?? null;
            } while (!empty($url));

            // Normalize and persist government bills (is_gov = true).
            self::dataFormat($allGovernmentBills, true);
        }
    }

    /**
     * Normalize bill data and persist both bill and sponsor politician (if present).
     *
     * Parameters:
     * - $bills: array of bill summary objects from the /bills endpoint.
     * - $is_gov: boolean flag indicating whether the bills are government-sponsored.
     *
     * Steps for each bill:
     * 1. Fetch bill detail page.
     * 2. Optionally fetch sponsor politician detail and upsert politician.
     * 3. Upsert the bill (including merged JSON payload).
     * 4. Retry on transient cURL errors.
     */
    private static function dataFormat($bills, $is_gov)
    {
        $openParliamentClass = new OpenParliamentClass();
        foreach ($bills as $bill) {
            while (true) {
                try {
                    // Fetch detailed bill information.
                    $bill_information = $openParliamentClass->getPolicyInformation($bill['url']);
                    logger([$bill['session'], $bill['number'], 'started']);
                    $data = null;

                    // If a sponsor politician URL exists, fetch their details.
                    if (!empty($bill_information['sponsor_politician_url'])) {
                        $data = $openParliamentClass->getPolicyInformation($bill_information['sponsor_politician_url']);
                    }

                    // Upsert sponsor politician if present.
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
                                // Guard against missing image key.
                                'politician_image' => isset($data['image']) ? 'https://openparliament.ca' . $data['image'] : '',
                                'politician_url' => $bill_information['sponsor_politician_url'],
                                'politician_json' => json_encode($data),
                            ],
                        );
                    }

                    // Upsert the Bill record, embedding both summary and detailed information.
                    Bill::updateOrCreate(
                        ['bill_url' => $bill['url']],
                        [
                            'session' => $bill['session'],
                            'introduced' => $bill['introduced'],
                            'name' => $bill['name']['en'],
                            'short_name' => !empty($bill_information['short_title']['en']) ? $bill_information['short_title']['en'] : $bill['name']['en'],
                            'number' => $bill['number'],
                            'politician' => $bill_information['sponsor_politician_url'] ?? 'government',
                            'is_government_bill' => $is_gov,
                            'bill_url' => $bill['url'],
                            // Store a JSON blob that merges summary and detailed info for future reference.
                            'bills_json' => json_encode(array_merge($bill, ['bill_information' => $bill_information])),
                        ],
                    );
                    logger([$bill['session'], $bill['number'], 'ended']);

                    // Success: break the retry loop and continue with next bill.
                    break;
                } catch (\Exception $e) {
                    // If we hit a cURL timeout/connection error, retry with exponential backoff.
                    if (str_contains($e->getMessage(), 'cURL error')) {
                        static $retryCount = 0;
                        $retryCount++;

                        if ($retryCount > 3) {
                            $retryCount = 1; // Cap retries to avoid excessively long waits
                        }

                        $sleepTime = 60 * pow(2, $retryCount - 1);
                        logger()->warning("Timeout fetching {$bill['url']}, retrying in {$sleepTime}s...");

                        // Progress bar countdown
                        for ($i = 0; $i < $sleepTime; $i++) {
                            $remaining = $sleepTime - $i;
                            $percent = ($i / $sleepTime) * 100;
                            echo "\rRetrying in {$remaining}s [" . str_repeat('=', (int) ($percent / 5)) . str_repeat(' ', 20 - (int) ($percent / 5)) . "] {$percent}%";
                            sleep(1);
                        }
                        echo "\n";
                        continue;
                    }
                    // Reset retry count and log non-retryable exceptions.
                    $retryCount = 0;

                    logger($e);
                    break;
                }
            }
        }
    }
}
