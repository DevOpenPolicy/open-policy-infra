<?php

use App\GenerateContentClass;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// Artisan::command('generate:data', function () {
//     $this->comment('started');
//     GenerateContentClass::generateMP();
//     GenerateContentClass::generateBill();
//     $this->comment('completed');
// })->purpose('Display an inspiring quote')->hourly();
