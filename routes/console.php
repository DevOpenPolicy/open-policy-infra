<?php

use App\Console\Commands\CheckIsFormerMp;
use App\Console\Commands\getSummaryForAllBills;
use App\Console\Commands\PopulateDebatesTable;
use App\Console\Commands\PopulatePoliticianActivity;
use App\Console\Commands\PopulatePoliticianProvince;
use App\Console\Commands\setUpCommitee;
use App\GenerateContentClass;
use App\Models\Politicians;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})
    ->purpose('Display an inspiring quote')
    ->hourly();

// ensure to clear cache before running commands
Artisan::command('generate:mp', function () {
    $this->comment('starting MP update');
    GenerateContentClass::generateMP();
    $this->comment('completed MP update');
})->purpose('Update MPs');

Artisan::command('generate:bills', function () {
    $this->comment('starting Bill update');
    GenerateContentClass::generateBill();
    $this->comment('completed Bill update');
})->purpose('Update Bills');

Artisan::command('check:former-mp', function () {
    $this->comment('start MP Former check update');
    (new CheckIsFormerMp())->handle();
    $this->comment('ending MP Former check update');
})->purpose('Check and update former MPs');

Artisan::command('generate:bill-summaries', function () {
    $this->comment('start bill summary update');
    (new getSummaryForAllBills())->handle();
    $this->comment('ending bill summary update');
})->purpose('Generate summaries for bills');

Artisan::command('generate:mp-activities', function () {
    $this->comment('start MP activity update');
    (new PopulatePoliticianActivity())->handle();
    $this->comment('ending MP activity update');
})->purpose('Populate MP activity logs');

Artisan::command('generate:debates', function () {
    $this->comment('start debate activity update');
    (new PopulateDebatesTable())->handle();
    $this->comment('ending debate activity update');
})->purpose('Populate debates table');

Artisan::command('generate:provinces', function () {
    $this->comment('start province activity update');
    (new PopulatePoliticianProvince())->handle();
    $this->comment('ending province activity update');
})->purpose('Populate politician provinces');

Artisan::command('generate:committees', function () {
    $this->comment('start committee activity update');
    (new setUpCommitee())->handle();
    $this->comment('ending committee activity update');
})->purpose('Setup committee activity');

Artisan::command('generate:all-data', function () {
    $this->comment('started');
    Artisan::call('generate:mp');
    Artisan::call('generate:bills');
    Artisan::call('check:former-mp');
    Artisan::call('generate:bill-summaries');
    Artisan::call('generate:mp-activities');
    Artisan::call('generate:debates');
    Artisan::call('generate:provinces');
    Artisan::call('generate:committees');
    $this->comment('completed');
})
->purpose('Run all update commands together')
->monthly();

