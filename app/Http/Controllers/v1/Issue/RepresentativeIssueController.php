<?php

namespace App\Http\Controllers\v1\Issue;

use App\Http\Controllers\Controller;
use App\Models\RepresentativeIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RepresentativeIssueController extends Controller
{
    public function getAllIssues(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $status = $request->input('status');

        $cacheKey = "user_{$user->id}_issues_{$search}_{$status}";

        $issues = Cache::remember($cacheKey, now()->addHours(1), function () use ($user, $search, $status) {
            return RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->select(
                    'representative_issues.id',
                    'representative_issues.name',
                    'representative_issues.summary',
                    'representative_issues.description',
                    'representative_issues.status',
                    'representative_issues.created_at as date',
                    'users.first_name',
                    'users.last_name'
                )
                ->where('representative_issues.representative_id', $user->id)
                ->when($status, function ($query) use ($status) {
                    $query->where('representative_issues.status', $status);
                })
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('representative_issues.name', 'like', "%{$search}%")
                          ->orWhere('representative_issues.summary', 'like', "%{$search}%");
                    });
                })
                ->orderBy('representative_issues.created_at', 'desc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $issues,
        ], 200);
    }

    public function createIssue(Request $request){
        RepresentativeIssue::create([
            'representative_id' => Auth::id(),
            'name' => $request->name,
            'summary' => $request->summary,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Issue created successfully',
        ], 201);
    }

    public function requestDeletion(Request $request){
        RepresentativeIssue::where('id', $request->id)->update([
            'status' => 'pending_deletion',
            'deletion_reason' => $request->deletion_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request sent successfully',
        ], 201);
    }
}
