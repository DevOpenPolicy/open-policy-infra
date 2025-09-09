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

Artisan::command('generate:data', function () {
    $this->comment('started');
    Artisan::call('cache:clear');
    $this->comment('starting MP update');
    GenerateContentClass::generateMP();
    $this->comment('completed MP update');
    $this->comment('starting Bill update');
    GenerateContentClass::generateBill();
    $this->comment('completed Bill update');
    Artisan::call('cache:clear');
    $this->comment('start MP Former check update');
    (new CheckIsFormerMp())->handle();
    $this->comment('ending MP Former check update');
    Artisan::call('cache:clear');
    $this->comment('start bill summary update');
    (new getSummaryForAllBills())->handle();
    $this->comment('ending bill summary update');
    Artisan::call('cache:clear');
    $this->comment('start MP activity update');
    (new PopulatePoliticianActivity())->handle();
    $this->comment('ending MP activity update');
    Artisan::call('cache:clear');   
    $this->comment('start debate activity update');
    (new PopulateDebatesTable())->handle();
    $this->comment('ending debate activity update');
    Artisan::call('cache:clear');
    $this->comment('start province activity update');
    (new PopulatePoliticianProvince())->handle();
    $this->comment('ending province activity update');
    Artisan::call('cache:clear');
    $this->comment('start committee activity update');
    (new setUpCommitee())->handle();
    $this->comment('ending committee activity update');
    $this->comment('completed');
})
    ->purpose('Update and setup system for bills and representatives')
    ->hourly();
