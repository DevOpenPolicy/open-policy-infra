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

        // Retrieve the latest parliament session, defaulting to '45-1' if none exists
        $parliamentSession = ParliamentSession::latest()->first();
        $session = $parliamentSession->session ?? '45-1';

        // Fetch bills without summaries in batches of 100 to optimize memory usage
        Bill::whereNull('summary')
            ->where('session', $session)
            ->chunk(100, function ($bills) use ($billClass) {
                // Arrays to build a single bulk update query using CASE WHEN
                $cases = []; // SQL WHEN clauses for dynamic value assignment
                $ids = []; // Bill IDs that will be updated
                $bindings = []; // Parameter bindings for prepared statement

                foreach ($bills as $bill) {
                    try {
                        // Attempt to retrieve the bill summary from external source
                        $summary = $billClass->getBillSummary($bill->bill_url);

                        // Only process if a valid summary was retrieved
                        if (!empty($summary)) {
                            $ids[] = $bill->id;

                            // Construct CASE WHEN clause with parameterized values
                            $cases[] = 'WHEN ? THEN ?';
                            $bindings[] = $bill->id;
                            $bindings[] = $summary;
                        }
                    } catch (\Exception $e) {
                        logger()->error("Failed to fetch summary for bill ID {$bill->id}: " . $e->getMessage());
                    }
                }

                // Execute bulk update if summaries were found
                if (!empty($cases)) {
                    // Build dynamic CASE statement with all bill updates
                    $query = "
                        UPDATE bills
                        SET summary = CASE id 
                            " . implode(' ', $cases) . " 
                            END,
                            introduced = introduced,
                            updated_at = NOW() 
                        WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                    ";

                    // Append bill IDs for WHERE clause filtering
                    $bindings = array_merge($bindings, $ids);

                    // Execute the prepared statement
                    DB::update($query, $bindings);

                    logger('✅ Updated ' . count($ids) . ' bills in one query.');
                }
            });

        logger('🎉 Finished updating all bill summaries.');
    }
}
