<?php

namespace App;

use GuzzleHttp\Client;
use Twilio\Rest\Client as TwilioRestClient;

class SMS
{
    public static function azure_send(array $phoneNumbers, string $message)
    {
        $body = [
            'from' => config("azure.sms_phone_number"),
            'message' => $message,
            'smsRecipients' => array_map(fn ($num) => ['to' => $num], $phoneNumbers)
        ];

        $endpoint = parse_url(config("azure.sms_endpoint"));

        $headers = [
            'Date' => gmdate("D, d M Y H:i:s T"),
            'host' => $endpoint['host'],
            'x-ms-content-sha256' => base64_encode(hash('sha256', json_encode($body), true)),
        ];

        $stringToSign = utf8_encode(implode("\n", [
            "POST",
            $endpoint['path'] . "?" . $endpoint['query'],
            implode(";", array_values($headers))
        ]));

        $headers['Authorization'] = implode("&", [
            "HMAC-SHA256 SignedHeaders=" . implode(";", array_keys($headers)),
            'Signature=' . base64_encode(hash_hmac('sha256', $stringToSign, base64_decode(config("azure.sms_key")), true))
        ]);

        $client = new Client();  // <-- this is guzzle

        $response = $client->post(config("azure.sms_endpoint"), [
            'headers' => $headers,
            'json' => $body
        ]);

    }

    public static function twilio_send(string $phoneNumber)
    {
        try{
            $twilio_client = new TwilioRestClient(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
            $twilio_client->verify->v2->services(config('services.twilio.verify_service'))
                            ->verifications
                            ->create($phoneNumber, "sms");

        }catch (\Exception $e){
            logger($e->getMessage());
            return false;
        }

        return true;
    }

    public static function twilio_verify(string $phoneNumber, string $code)
    {
        try{
            $twilio_client = new TwilioRestClient(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $verification_check = $twilio_client->verify->v2->services(config('services.twilio.verify_service'))
                ->verificationChecks
                ->create([
                            "to" => $phoneNumber,
                            "code" => $code
                        ]
                    );
            return $verification_check->status == 'approved';
        }catch (\Exception $e){
            logger($e->getMessage());
            return false;
        }

        return true;
    }
}
