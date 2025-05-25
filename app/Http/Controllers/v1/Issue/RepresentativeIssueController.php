<?php

namespace App\Http\Controllers\v1\Issue;

use App\Http\Controllers\Controller;
use App\Models\RepresentativeIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RepresentativeIssueController extends Controller
{
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
