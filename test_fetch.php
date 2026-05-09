<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$url = 'https://openparliament.ca/bills/45-1/C-2/';
$response = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
])->get($url);

echo "Status: " . $response->status() . "\n";
echo "Body length: " . strlen($response->body()) . "\n";
echo substr($response->body(), 0, 500) . "\n";
