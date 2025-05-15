<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class SetupSystem implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // logger('Setting up the system...');
        // Artisan::call('generate:data');
        // logger('Setting up the system 2');
        // Artisan::call('app:check-is-former-mp');
        // logger('Setting up the system 3');
        // Artisan::call('app:get-summary-for-all-bills');
        // logger('Setting up the system 4');
        // Artisan::call('app:populate-debates-table');
        // logger('Setting up the system 5');
        // Artisan::call('app:populate-politician-activity');
        // logger('Setting up the system 6');
        // Artisan::call('app:populate-politician-province');
        // logger('Setting up the system 6');
        // Artisan::call('app:set-up-committee');
        // logger('Set up done');
    }
}
