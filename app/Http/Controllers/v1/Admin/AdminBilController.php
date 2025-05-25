<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillVoteCast;
use App\Models\Politicians;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use stdClass;

class AdminBilController extends Controller
{
    public function getBills(){
        $bills = DB::table('bills')
        ->select([
            'bills.id',
            'bills.name',
            'bills.number AS code',
            'bills.introduced',
            'politicians.name AS sponsor',
            'bills.is_government_bill',
            DB::raw("SUM(CASE WHEN bill_vote_casts.is_supported = 1 THEN 1 ELSE 0 END) AS supported_count"),
            DB::raw("SUM(CASE WHEN bill_vote_casts.is_supported = 0 THEN 1 ELSE 0 END) AS opposed_count"),
        ])
        ->when(request('search'), function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('bills.name', 'like', "%{$search}%")
                  ->orWhere('bills.number', 'like', "%{$search}%")
                  ->orWhere('politicians.name', 'like', "%{$search}%");
            });
        })
        ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
        ->leftJoin('bill_vote_casts', 'bills.bill_url', '=', 'bill_vote_casts.bill_url')
        ->whereNotIn('bills.number', ['c-1', 's-1'])
        ->groupBy(
            'bills.id',
            'bills.name',
            'bills.number',
            'bills.introduced',
            'politicians.name',
            'bills.is_government_bill'
        )
        ->orderBy('supported_count', 'desc')
        ->orderBy('opposed_count', 'desc')  
        ->orderBy('bills.id', 'asc')
        ->paginate(10);

        return response()->json([
            'success' => true,
            'bills' => $bills,
            'count' => Number::abbreviate(Bill::count(),2)
        ]);
    }

    public function getBill($id){
        $bills = Bill::find($id);
        if(!$bills){
            return response()->json([
                'success' => true,
                'message' => 'Bill not found',
            ]);
        }

        // return $bills;

        $sponsor = Politicians::where('politician_url', $bills->politician)->first();

        $json = json_decode($bills->bills_json);
        $temp = new stdClass();
        $temp->title = $bills->name;
        $temp->sponsor = $sponsor?->name;
        $temp->sponsor_party = $sponsor?->party_short_name;
        $temp->summary = $bills->summary;
        $temp->status = $json->bill_information->status->en;

        $stats = BillVoteCast::where('bill_url', $bills->bill_url)
            ->join('users', 'bill_vote_casts.user_id', '=', 'users.id')
            ->select(
                'bill_vote_casts.is_supported',
                'users.gender',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('bill_vote_casts.is_supported', 'users.gender')
            ->get();

        // Initialize structure
        $totalSupported = ['total' => 0, 'male' => 0, 'female' => 0];
        $totalOpposed   = ['total' => 0, 'male' => 0, 'female' => 0];

        // Populate counts
        foreach ($stats as $stat) {
            $type = $stat->is_supported ? 'totalSupported' : 'totalOpposed';
            $gender = strtolower($stat->gender); // assuming values are "Male" or "Female"

            if ($type === 'totalSupported') {
                $totalSupported['total'] += $stat->total;
                if ($gender === 'male') {
                    $totalSupported['male'] += $stat->total;
                } elseif ($gender === 'female') {
                    $totalSupported['female'] += $stat->total;
                }
            } else {
                $totalOpposed['total'] += $stat->total;
                if ($gender === 'male') {
                    $totalOpposed['male'] += $stat->total;
                } elseif ($gender === 'female') {
                    $totalOpposed['female'] += $stat->total;
                }
            }
        }

        // Format numbers with commas
        function formatNumber($num) {
            return$num;
            return number_format($num);
        }

        $temp->finalResult = [
            'totalSupported' => [
                'total'   => formatNumber($totalSupported['total']),
                'male'    => formatNumber($totalSupported['male']),
                'females' => formatNumber($totalSupported['female']),
            ],
            'totalOpposed' => [
                'total'   => formatNumber($totalOpposed['total']),
                'male'    => formatNumber($totalOpposed['male']),
                'females' => formatNumber($totalOpposed['female']),
            ],
        ];

        $temp->votes = [
            [
                'name' => 'Votes',
                'Supported'    => formatNumber($totalSupported['total']),
                'Opposed' => formatNumber($totalOpposed['total']),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $temp
        ]);

    }
}
