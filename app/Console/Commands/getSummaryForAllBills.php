<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\ParliamentSession;
use App\Service\v1\BillClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GetSummaryForAllBills extends Command
{
    protected $signature = 'app:get-summary-for-all-bills';
    protected $description = 'Fetch and update missing summaries for bills in the latest parliament session';

    public function handle()
    {
        $billClass = new BillClass();

        // Get latest parliament session or fallback
        $parliamentSession = ParliamentSession::latest()->first();
        $session = $parliamentSession->session ?? '45-1';

        // Process bills in chunks
        Bill::whereNull('summary')
            ->where('session', $session)
            ->chunk(100, function ($bills) use ($billClass) {
                $cases = [];   // Stores WHEN clauses
                $ids = [];     // IDs of bills to update
                $bindings = []; // Bound values for query

                foreach ($bills as $bill) {
                    try {
                        $summary = $billClass->getBillSummary($bill->bill_url);

                        if (!empty($summary)) {
                            $ids[] = $bill->id;

                            // Build CASE WHEN for this bill
                            $cases[] = "WHEN ? THEN ?";
                            $bindings[] = $bill->id;
                            $bindings[] = $summary;
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to fetch summary for bill ID {$bill->id}: " . $e->getMessage());
                    }
                }

                // Run a single bulk update if we have updates
                if (!empty($cases)) {
                    $query = "
                        UPDATE bills 
                        SET summary = CASE id
                            " . implode(' ', $cases) . "
                        END
                        WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                    ";

                    // Add ids again for the WHERE clause
                    $bindings = array_merge($bindings, $ids);

                    DB::update($query, $bindings);

                    $this->info("✅ Updated " . count($ids) . " bills in one query.");
                }
            });

        $this->info('🎉 Finished updating all bill summaries.');
    }
}
