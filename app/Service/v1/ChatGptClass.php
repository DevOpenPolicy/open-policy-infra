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
        $systemMessage = "You are a helpful assistant that answers using the information provided.";

        $userPrompt = "Bill Number: $billNumber\nSummary: $summary\n";
        if ($instruction) {
            $userPrompt .= "Instruction: $instruction\n";
        }

        $userPrompt .= "answer using the information above.";

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

    public function generateBillResponseForLink(string $link, ?string $instruction = null): string
    {        
        try {
  
            $webContent = $this->fetchWebContent($link);
            if (empty($webContent)) {
                return "Unable to retrieve content from the provided link. Please check if the link is accessible.";
            }
            $systemMessage = "You are a helpful assistant that answers using the information provided.";

            $userPrompt = "Bill Link: $link\n\n";
            $userPrompt .= "Bill Content:\n$webContent\n\n";
            
            if ($instruction) {
                $userPrompt .= "Instruction: $instruction\n";
            }

            $userPrompt .= "answer using the information above.";

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
                    'max_tokens' => 2000,
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            $aiResponse = $body['choices'][0]['message']['content'] ?? 'No response generated.';

            return $aiResponse;
            
        } catch (\Exception $e) {
            error_log('Error in generateBillResponseForLink: ' . $e->getMessage());
            return 'Error processing the bill link: ' . $e->getMessage();
        }
    }

    /**
     * Fetch content from a web URL
     */
    private function fetchWebContent(string $url): string
    {
        try {
            error_log('Fetching content from: ' . $url);
            
            $response = $this->client->get($url, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);

            $html = $response->getBody()->getContents();
            
            // Extract text content from HTML
            $text = $this->extractTextFromHtml($html);
            
            // Limit content length to avoid token limits
            $limitedText = substr($text, 0, 8000);
            
            return $limitedText;
            
        } catch (\Exception $e) {
            error_log('Error fetching web content: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract clean text content from HTML
     */
    private function extractTextFromHtml(string $html): string
    {
        // Remove script and style elements
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Convert HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Remove HTML tags
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    public function generateIssueResponse(string $summary, ?string $instruction = null): string
    {
        $systemMessage = "You are a helpful assistant that answers using the information provided";

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
    public function generateAceResponse(array $messages): string
    {
        $systemMessage = "You are Ace, an intelligent policy research companion. You help users understand legislation, policy, and bills. Be professional, concise, and informative.";

        $formattedMessages = [['role' => 'system', 'content' => $systemMessage]];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => $formattedMessages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        return $body['choices'][0]['message']['content'] ?? 'No response generated.';
    }

    public function extractSearchTerms(string $query): array
    {
        $systemMessage = "You are a policy research assistant. Extract the most relevant search keywords (3-5 words) from the user's natural language query about bills or legislation. Return only the keywords separated by spaces.";

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $query],
                ],
                'temperature' => 0.3,
                'max_tokens' => 50,
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        return array_filter(explode(' ', $content));
    }

    public function generateSearchResponse(string $query, $bills): string
    {
        $systemMessage = "You are Ace, an intelligent policy research companion. A user asked: '$query'. Based on their query, I found some bills. Briefly explain how these bills relate to their interest and invite them to explore further. Be concise.";
        
        $billContext = "";
        foreach ($bills as $bill) {
            $number = is_array($bill) ? ($bill['number'] ?? 'N/A') : ($bill->number ?? 'N/A');
            $name = is_array($bill) ? ($bill['short_name'] ?? $bill['name'] ?? 'Untitled') : ($bill->short_name ?? $bill->name ?? 'Untitled');
            $billContext .= "- {$number}: {$name}\n";
        }

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => "Found bills:\n$billContext"],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]
        ]);
        // change

        $body = json_decode($response->getBody(), true);
        return $body['choices'][0]['message']['content'] ?? 'Here are the bills I found for you.';
    }

    public function extractMemberSearchTerms(string $query): array
    {
        $systemMessage = "You are a policy research assistant. Extract relevant search keywords (3-5 words) from the user's natural language query about Canadian Members of Parliament. Focus on names, parties, and provinces. Return only the keywords separated by spaces.";

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $query],
                ],
                'temperature' => 0.3,
                'max_tokens' => 50,
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        return array_filter(explode(' ', $content));
    }

    public function generateMemberSearchResponse(string $query, $members): string
    {
        $systemMessage = "You are Ace, an intelligent policy research companion. A user asked about MPs: '$query'. Based on their query, I found some Members of Parliament. Briefly explain how these members relate to their interest. Be concise and professional.";
        
        $memberContext = "";
        foreach ($members as $member) {
            $name = is_array($member) ? ($member['name'] ?? 'N/A') : ($member->name ?? 'N/A');
            $party = is_array($member) ? ($member['party_name'] ?? 'N/A') : ($member->party_name ?? 'N/A');
            $province = is_array($member) ? ($member['province_name'] ?? 'N/A') : ($member->province_name ?? 'N/A');
            $memberContext .= "- {$name} ({$party}, {$province})\n";
        }

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => "Context:\n$memberContext\n\nQuery: $query"],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['choices'][0]['message']['content'] ?? 'I found some members that might interest you.';
    }
}
