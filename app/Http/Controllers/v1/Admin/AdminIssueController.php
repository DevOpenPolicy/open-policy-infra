<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssueVoteCast;
use App\Models\Politicians;
use App\Models\RepresentativeIssue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use stdClass;

class AdminIssueController extends Controller
{
    public function getIssues(){
        // deleted, pending, pending_deletion, approved

        $representative_issues = DB::table('representative_issues')
            ->select([
                'representative_issues.id',
                'representative_issues.name',
                'representative_issues.status',
                'representative_issues.created_at',
                'representative_issues.summary',
                'representative_issues.description',
                'users.first_name',
                'users.last_name',
                DB::raw("SUM(CASE WHEN issue_vote_casts.is_supported = 1 THEN 1 ELSE 0 END) AS supported_count"),
                DB::raw("SUM(CASE WHEN issue_vote_casts.is_supported = 0 THEN 1 ELSE 0 END) AS opposed_count"),
            ])
            // ->where('representative_issues.status', '!=', 'deleted')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->leftJoin('issue_vote_casts', 'representative_issues.id', '=', 'issue_vote_casts.issue_id')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%");
                });
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('representative_issues.status', $status);
            })
            ->groupBy(
                'representative_issues.id',
                'representative_issues.name',
                'representative_issues.status',
                'representative_issues.created_at',
                'representative_issues.summary',
                'representative_issues.description',
                'users.first_name',
                'users.last_name'
            )
            // ->orderBy('supported_count', 'desc')
            // ->orderBy('opposed_count', 'desc')
            ->orderBy('representative_issues.id', 'asc')
            ->paginate(10);


        $total = DB::table('representative_issues')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%");
                });
            })
            ->count();

        $approved = DB::table('representative_issues')
            ->where('status','approved')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%");
                });
            })
            ->count();

        $pending_deletion = DB::table('representative_issues')
            ->where('status','pending_deletion')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%");
                });
            })
            ->count();

        $pending = DB::table('representative_issues')
            ->where('status','pending')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%");
                });
            })
            ->count();

        $deleted = DB::table('representative_issues')
            ->where('status','deleted')
            ->join('users', 'representative_issues.representative_id', '=', 'users.id')
            ->when(request('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('representative_issues.name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.summary', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('representative_issues.description', 'like', "%{$search}%");
                });
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => $representative_issues,
            'approved' => Number::abbreviate($approved),
            'pending_deletion' => Number::abbreviate($pending_deletion),
            'pending' => Number::abbreviate($pending),
            'deleted' => Number::abbreviate($deleted),
            'total' => Number::abbreviate($total),
        ]);
    }

    public function getIssue($id){
        $representative = RepresentativeIssue::find($id);
        if(!$representative){
            return response()->json([
                'success' => true,
                'message' => 'Representative not found',
            ]);
        }

        $user = User::find($representative->representative_id);
        $sponsor = Politicians::where('name','like',"%$user->first_name%")->orWhere('name','like',"%$user->last_name%")->first();

        $temp = new stdClass();
        $temp->title = $representative->name;
        $temp->sponsor = $sponsor?->name;
        $temp->sponsor_party = $sponsor?->party_short_name;
        $temp->summary = $representative->summary;
        $temp->description = $representative->description;
        $temp->status = $representative->status;
        $temp->deletion_reason = $representative->deletion_reason;
        

        $stats = IssueVoteCast::where('issue_id', $representative->id)
            ->join('users', 'issue_vote_casts.user_id', '=', 'users.id')
            ->select(
                'issue_vote_casts.is_supported',
                'users.gender',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('issue_vote_casts.is_supported', 'users.gender')
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

    public function updateIssues($id, Request $request){
        logger($id);
        logger($request->status);

        if($request->status == 'waiting'){
            $status = 'pending';

        }elseif($request->status == 'delete'){
            $status = 'deleted';

        }elseif($request->status == 'approve'){
            $status = 'approved';

        }else{
            return response()->json([
                'success' => false,
                'message' => 'Wrong status sent, check again'
            ]);
        }

        $issue = RepresentativeIssue::find($id);
        if(!$issue){
            return response()->json([
                'success' => false,
                'message' => 'Representative issue not found, refresh and try again'
            ]);
        }

        $issue->status = $status;
        $issue->save();

        return response()->json([
            'success' => true,
            'message' => 'Representative Issue status has updated successfully'
        ]);
    }
}
