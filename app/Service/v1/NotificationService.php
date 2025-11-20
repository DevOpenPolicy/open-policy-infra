<?php

namespace App\Service\v1;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private $expoApiUrl = 'https://exp.host/--/api/v2/push/send';
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Check if a token is a valid Expo push token
     */
    public function isExpoPushToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        // Expo push tokens start with ExponentPushToken[ or ExponenPushToken[
        // and end with ]
        return preg_match('/^ExponentPushToken\[.+\]$/', $token) === 1 ||
               preg_match('/^ExpoPushToken\[.+\]$/', $token) === 1;
    }

    /**
     * Chunk push notifications (Expo recommends max 100 per request)
     */
    public function chunkPushNotifications(array $messages): array
    {
        $chunks = [];
        $chunkSize = 100;

        for ($i = 0; $i < count($messages); $i += $chunkSize) {
            $chunks[] = array_slice($messages, $i, $chunkSize);
        }

        return $chunks;
    }

    /**
     * Send push notifications to recipients
     *
     * @param array $notification {
     *   @var string $id
     *   @var string $title
     *   @var string $message
     * }
     * @param array $recipients {
     *   @var int|string $id
     *   @var string|null $pushToken
     * }[]
     * @return array
     */
    public function sendPushNotifications(
        array $notification,
        array $recipients
    ): array {
        // Filter valid recipients
        $validRecipients = array_filter($recipients, function ($recipient) {
            return isset($recipient['pushToken']) &&
                   $this->isExpoPushToken($recipient['pushToken']);
        });

        Log::info('Valid recipients for push notification:', [
            'count' => count($validRecipients),
            'recipients' => $validRecipients
        ]);

        if (empty($validRecipients)) {
            Log::info('No valid recipients found for push notifications');
            return [
                'success' => false,
                'message' => 'No valid recipients found',
                'sent' => 0
            ];
        }

        // Build messages
        $messages = array_map(function ($recipient) use ($notification) {
            $data = [];
            if (isset($notification['id']) && !empty($notification['id'])) {
                $data['id'] = $notification['id'];
            }
            
            return [
                'to' => $recipient['pushToken'],
                'sound' => 'default',
                'title' => $notification['title'],
                'body' => $notification['message'],
                'data' => $data,
            ];
        }, $validRecipients);

        // Chunk messages
        $chunks = $this->chunkPushNotifications($messages);
        $results = [];
        $totalSent = 0;

        // Send each chunk
        foreach ($chunks as $chunk) {
            try {
                $response = $this->client->post($this->expoApiUrl, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $chunk,
                ]);

                $responseData = json_decode($response->getBody()->getContents(), true);
                $results[] = $responseData;

                // Count successful sends
                if (isset($responseData['data'])) {
                    foreach ($responseData['data'] as $ticket) {
                        if (isset($ticket['status']) && $ticket['status'] === 'ok') {
                            $totalSent++;
                        }
                    }
                }

                Log::info('Push notification result:', [
                    'chunk_size' => count($chunk),
                    'response' => $responseData
                ]);
            } catch (\Exception $error) {
                Log::error('Error sending push notification:', [
                    'error' => $error->getMessage(),
                    'trace' => $error->getTraceAsString()
                ]);
                $results[] = [
                    'error' => $error->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Sent {$totalSent} notifications",
            'sent' => $totalSent,
            'results' => $results
        ];
    }
}

