<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Service\v1\BillClass;
use Illuminate\Console\Command;

class getSummaryForAllBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-summary-for-all-bills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $billClass = new BillClass();

        Bill::where('summary',null)
        ->chunk(100, function ($bills) use($billClass) {
            foreach ($bills as $bill) {
                $summary = $billClass->getBillSummary($bill->bill_url);
                $bill->summary = $summary;
                $bill->save();
            }
            
        });

        dd('done');
    }
}
