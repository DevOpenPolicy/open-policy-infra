<?php

namespace App\Http\Controllers\v1\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $recentAlerts = [];
        $relevantBills = [];
        $highImpactBills = [];
        $industryKeywords = [
            'Fintech' => ['financial', 'bank', 'credit', 'tax', 'payment', 'money', 'investment'],
            'Healthcare' => ['health', 'medical', 'hospital', 'drug', 'pharmaceutical', 'care'],
            'Energy' => ['energy', 'oil', 'gas', 'climate', 'environment', 'electricity', 'nuclear'],
            'Tech' => ['technology', 'digital', 'internet', 'data', 'privacy', 'artificial intelligence', 'software'],
            'Telecom' => ['telecom', 'broadcasting', 'spectrum', 'communication', 'phone']
        ];

        if ($user) {
            $unreadAlertsCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
            
            $recentAlerts = Notification::where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($n) {
                    return [
                        'id' => $n->id,
                        'alert_type' => 'new_bill', // Default for now
                        'title' => $n->title,
                        'message' => $n->message,
                        'created_at' => $n->created_at->toIso8601String(),
                    ];
                });

            // Logic for Relevant Bills based on Industry
            $organization = $user->organization ?: \App\Models\Organization::where('user_id', $user->id)->first();
            if ($organization && !empty($organization->industries)) {
                $keywords = [];
                foreach ($organization->industries as $ind) {
                    if (isset($industryKeywords[$ind])) {
                        $keywords = array_merge($keywords, $industryKeywords[$ind]);
                    } else {
                        $keywords[] = strtolower($ind);
                    }
                }

                $relevantBills = Bill::where(function($query) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $query->orWhere('name', 'like', "%{$kw}%")
                              ->orWhere('short_name', 'like', "%{$kw}%");
                    }
                })
                ->limit(3)
                ->get()
                ->map(function($b) {
                    return [
                        'id' => $b->id,
                        'status' => 'active',
                        'billNumber' => $b->number,
                        'title' => $b->short_name ?: $b->name,
                        'summary' => $b->name,
                    ];
                });
            }
        }

        // High Impact Policies (Simulated for now - maybe government bills)
        $highImpactBills = Bill::where('is_government_bill', 1)
            ->latest('introduced')
            ->limit(5)
            ->get()
            ->map(function($b) {
                return [
                    'id' => $b->id,
                    'title' => $b->short_name ?: $b->name,
                    'risk_level' => 'medium',
                    'industry' => 'General',
                ];
            });

        // Activity Trend (Last 6 months)
        $activityTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Bill::whereYear('introduced', $month->year)
                ->whereMonth('introduced', $month->month)
                ->count();
            
            $activityTrend[] = [
                'month' => $month->format('M'),
                'bills' => $count,
            ];
        }

        // Industry Activity
        $industryActivity = [];
        foreach ($industryKeywords as $industry => $keywords) {
            $count = Bill::where(function($query) use ($keywords) {
                foreach ($keywords as $kw) {
                    $query->orWhere('name', 'like', "%{$kw}%")
                          ->orWhere('short_name', 'like', "%{$kw}%");
                }
            })->count();

            $industryActivity[] = [
                'industry' => $industry,
                'count' => $count,
            ];
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
                'relevantBills' => $relevantBills,
                'highImpactBills' => $highImpactBills,
                'recentAlerts' => $recentAlerts,
                'activityTrend' => $activityTrend,
                'industryActivity' => $industryActivity,
            ]
        ]);
    }
}
