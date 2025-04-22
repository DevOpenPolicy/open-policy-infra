<?php

use App\GenerateContentClass;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command('generate:data', function () {
    $this->comment('started');
    GenerateContentClass::generateMP();
    GenerateContentClass::generateBill();
    $this->comment('completed');
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('upload:data', function () {
    // $tables = ['bills','parliament_sessions','politicians'];
    // foreach ($tables as $table) {
    //     $chunkSize = 500;
    //     $targetUrl = 'https://open-policy-backend-drf8ffeeeehhhhd2.canadacentral-01.azurewebsites.net/api/upload-db';
    //     $this->comment('Phase 1');

    //     DB::table($table)->orderBy('id')->chunk($chunkSize, function ($rows) use ($table, $targetUrl) {
    //         $this->comment('Phasing');
    //         $dataArray = $rows->map(fn ($row) => (array) $row)->toArray();

    //         Http::post($targetUrl, [
    //             'table' => $table,
    //             'data' => $dataArray,
    //         ]);
    //     });

    //     $this->comment('Done');
    // }


    
})->purpose('Display an inspiring quote')->hourly();
