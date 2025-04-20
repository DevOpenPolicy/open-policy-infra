<?php

namespace App;

use GuzzleHttp\Client;

class SMS
{
    public static function send(array $phoneNumbers, string $message)
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
}
