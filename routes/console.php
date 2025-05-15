<?php

use App\GenerateContentClass;
use App\Models\Politicians;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command('generate:data', function () {
    $this->comment('started');
    GenerateContentClass::generateMP();
    GenerateContentClass::generateBill();
    $this->comment('completed');
})->purpose('Display an inspiring quote')->hourly();

// Artisan::command('upload:data', function () {
//     $tables = ['politicians'];
//     foreach ($tables as $table) {
//         $chunkSize = 1000;
//         $targetUrl = 'https://open-policy-backend-drf8ffeeeehhhhd2.canadacentral-01.azurewebsites.net/api/upload-db';
//         $this->comment('Phase 1');

//         DB::table($table)->orderBy('id')->chunk($chunkSize, function ($rows) use ($table, $targetUrl) {
//             $this->comment('Phasing');
//             $dataArray = $rows->map(fn ($row) => (array) $row)->toArray();  
// $this->comment(count($dataArray));
//             Http::post($targetUrl, [
//                 'table' => $table,
//                 'data' => $dataArray,
//             ]);
//         });

//         $this->comment('Done');
//     }


    
// })->purpose('Display an inspiring quote')->hourly();
