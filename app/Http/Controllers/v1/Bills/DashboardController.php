<?php

namespace App\Http\Controllers\v1\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary()
    {
        $user = Auth::user();

        // 1. New Bills (Last 7 days)
        $newBillsCount = Bill::where('introduced', '>=', Carbon::now()->subDays(7))->count();

        // 2. Bills Passed (Law)
        $billsPassedCount = Bill::where('bills_json', 'like', '%"status":{"en":"Law (royal assent given)"}%')->count();

        // 3. Active Bills (Exclude Dead, Defeated, Law, and weird procedural relics)
        $activeBillsCount = Bill::where('bills_json', 'not like', '%"status":{"en":"Law (royal assent given)"}%')
            ->where('bills_json', 'not like', '%"status":{"en":"Dead"}%')
            ->where('bills_json', 'not like', '%"status":{"en":"Defeated"}%')
            ->where('bills_json', 'not like', '%"status":{"en":"Not a real bill%')
            ->count();

        // 4. Policy Alerts (Unread notifications)
        $unreadAlertsCount = 0;
        if ($user) {
            $unreadAlertsCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'activeBills' => $activeBillsCount,
                    'newBills' => $newBillsCount,
                    'passedBills' => $billsPassedCount,
                    'alertCount' => $unreadAlertsCount,
                ],
                'relevantBills' => [], // Placeholder for future logic
                'highImpactBills' => [], // Placeholder for future logic
                'recentAlerts' => [], // Placeholder for future logic
            ]
        ]);
    }
}
