<?php

namespace App\Service\v1;

use GuzzleHttp\Client;

class ChatGptClass
{
    protected $client;
    protected $apiKey;


    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function generateBillResponse(string $billNumber, string $summary, ?string $instruction = null): string
    {
        $systemMessage = "You are a helpful assistant that ONLY answers questions about Canadian Parliament bills. " .
                         "If the user asks anything unrelated, politely say: 'I'm sorry, I can only answer questions about bills.'";

        $userPrompt = "Bill Number: $billNumber\nSummary: $summary\n";
        if ($instruction) {
            $userPrompt .= "Instruction: $instruction\n";
        }

        $userPrompt .= "Only answer using the information above.";

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        return $body['choices'][0]['message']['content'] ?? 'No response generated.';
    }

    public function generateIssueResponse(string $summary, ?string $instruction = null): string
    {
        $systemMessage = "You are a helpful assistant that ONLY answers questions about issues raised by Members of the Canadian Parliament that are NOT formal bills. " .
                 "These can include motions, petitions, statements, or debates initiated by MPs. " .
                 "If the user asks about formal bills or anything unrelated, politely say: 'I'm sorry, I can only answer questions about non-bill issues raised by Members of Parliament.'";

        $userPrompt = "Summary: $summary\n";
        if ($instruction) {
            $userPrompt .= "Instruction: $instruction\n";
        }

        $userPrompt .= "Only answer using the information above.";

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        return $body['choices'][0]['message']['content'] ?? 'No response generated.';
    }
}
