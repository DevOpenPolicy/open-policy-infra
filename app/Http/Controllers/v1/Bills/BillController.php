<?php

namespace App\Http\Controllers\v1\Bills;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillVoteCast;
use App\Models\BillVoteSummary;
use App\Models\IssueVoteCast;
use App\Models\Politicians;
use App\Models\RepresentativeIssue;
use App\Models\SavedBill;
use App\Models\SavedIssue;
use App\RoleManager;
use App\Service\v1\BillClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BillController extends Controller
{
    private $billClass;
    private $openParliamentClass;
    public function __construct()
    {
        $this->billClass = new BillClass();
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function getAllBills()
    {
        // sorting by short_name, name, number, politician_name
        $search = request('search');
        $type = request('type');

        if ($type == 'All Bills') {
            $type = null;
        } elseif ($type == 'Private Member Bills') {
            $type = 0;
        } elseif ($type == 'Government Bills') {
            $type = 1;
        }

        $bills = Cache::remember("app_bills_page_{$search}_{$type}", now()->addDays(7), function () use ($search, $type) {
            return Bill::select('bills.id','bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'politicians.name as politician_name')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bills.session', '45-1')
                ->whereNotIn('bills.number', ['c-1', 's-1'])
                ->where(function ($query) use ($search) {
                    $query
                        ->where('bills.name', 'like', "%{$search}%")
                        ->orWhere('bills.short_name', 'like', "%{$search}%")
                        ->orWhere('bills.number', 'like', "%{$search}%")
                        ->orWhere('politicians.name', 'like', "%{$search}%");
                })
                ->when(isset($type), function ($query) use ($type) {
                    return $query->where('bills.is_government_bill', $type);
                })
                ->get();
        });

        return response()->json(
            [
                'success' => true,
                'data' => $bills,
            ],
            200,
        );
    }

    public function userBills()
    {
        $search = request('search');
        $type = request('type');

        $user = Auth::user();
        if ($type == 'Saved Bills') {
            $bills = SavedBill::where('saved_bills.is_saved', 1)
                ->select('bills.id','bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'politicians.name as politician_name')
                ->join('bills', 'saved_bills.bill_url', '=', 'bills.bill_url')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where(function ($query) use ($search) {
                    $query
                        ->where('bills.name', 'like', "%{$search}%")
                        ->orWhere('bills.short_name', 'like', "%{$search}%")
                        ->orWhere('bills.number', 'like', "%{$search}%")
                        ->orWhere('politicians.name', 'like', "%{$search}%");
                })
                ->where('saved_bills.user_id', $user->id)
                ->where('bills.session', '45-1')
                ->whereNotIn('bills.number', ['c-1', 's-1'])
                ->orderBy('saved_bills.created_at', 'desc')
                ->get();
        } elseif ($type == 'Vote Cast') {
            $bills = BillVoteCast::select('bills.id','bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'politicians.name as politician_name')
                ->join('bills', 'bill_vote_casts.bill_url', '=', 'bills.bill_url')
                ->where(function ($query) use ($search) {
                    $query
                        ->where('bills.name', 'like', "%{$search}%")
                        ->orWhere('bills.short_name', 'like', "%{$search}%")
                        ->orWhere('bills.number', 'like', "%{$search}%")
                        ->orWhere('politicians.name', 'like', "%{$search}%");
                })
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bill_vote_casts.user_id', $user->id)
                ->where('bills.session', '45-1')
                ->whereNotIn('bills.number', ['c-1', 's-1'])
                ->orderBy('bill_vote_casts.created_at', 'desc')
                ->get();
        }elseif ($type == 'Issues Raised') {
            $bills = RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->where('users.id', $user->id)
                ->where('representative_issues.status','approved')
                ->select('representative_issues.name', 'representative_issues.summary', 'representative_issues.created_at as date', 'representative_issues.id')
                ->where('users.role', RoleManager::REPRESENTATIVE)
                ->get();
        }elseif ($type == 'Saved Issues') {
            $bills = SavedIssue::where('saved_issues.is_saved', 1)
                ->join('representative_issues', 'saved_issues.issue_id', '=', 'representative_issues.id')
                ->select('representative_issues.name', 'representative_issues.summary', 'representative_issues.created_at as date', 'representative_issues.id')
                ->where('saved_issues.user_id', $user->id)
                ->get();
        }elseif ($type == 'Voted Issues') {
            $bills = IssueVoteCast::where('issue_vote_casts.is_supported', 1)
            ->join('representative_issues', 'issue_vote_casts.issue_id', '=', 'representative_issues.id')
            ->select('representative_issues.name', 'representative_issues.summary', 'representative_issues.created_at as date', 'representative_issues.id')
            ->where('issue_vote_casts.user_id', $user->id)
            ->get();
        }

        return response()->json(
            [
                'success' => true,
                'data' => $bills,
            ],
            200,
        );
    }

    public function getBillNumber($number)
    {
        $bill = Cache::remember("app_bill_{$number}", now()->addDays(7), function () use ($number) {
            $data = Bill::select('bills.*', 'politicians.name as politician_name', 'politicians.id as politician_id')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bills.session', '45-1')
                ->where('bills.id', $number)
                ->first();

            if (!$data) {
                return null;
            }

            // Decode JSON safely
            $data->bills_json = json_decode($data->bills_json);
            $data->summary = $this->billClass->getBillSummary($data->bill_url);

            // Prepare vote URLs
            $voteUrls = $data->bills_json->bill_information->vote_urls ?? [];

            // Fetch existing vote summaries
            $existingVotes = BillVoteSummary::whereIn('vote_url', $voteUrls)->get()->keyBy('vote_url');

            foreach ($voteUrls as $voteUrl) {
                // Skip if already saved
                if ($existingVotes->has($voteUrl)) {
                    continue;
                }

                // Fetch from openParliament API
                $voteData = $this->openParliamentClass->getPolicyInformation($voteUrl);

                if (!$voteData) {
                    continue;
                }

                // Store new vote summary
                BillVoteSummary::create([
                    'bill_url' => $voteData['bill_url'] ?? '',
                    'session' => $voteData['session'] ?? '',
                    'description' => $voteData['description']['en'] ?? '',
                    'result' => $voteData['result'] ?? '',
                    'vote_url' => $voteData['url'] ?? $voteUrl,
                    'vote_json' => json_encode($voteData),
                ]);
            }

            // Refresh with all vote summaries
            $data->votes = BillVoteSummary::select('vote_url', 'description', 'result')->whereIn('vote_url', $voteUrls)->get();

            if ($data->votes->count() == 0) {
                $data->app_summary = collect([
                    ['id' => 1, 'title' => 'Sponsor:', 'value' => $data->politician_name], 
                    ['id' => 2, 'title' => 'Status:', 'value' => $data->bills_json->bill_information->status->en ?? 'Unknown'], 
                    ['id' => 3, 'title' => 'Summary:', 'value' => $data->summary]])
                ->map(fn($item) => (object) $item);
            } else {
                $data->app_summary = collect([
                    ['id' => 1, 'title' => 'Sponsor:', 'value' => $data->politician_name], 
                    ['id' => 2, 'title' => 'Status:', 'value' => $data->bills_json->bill_information->status->en ?? 'Unknown'], 
                    ['id' => 3, 'title' => 'Summary:', 'value' => $data->summary], 
                    ['id' => 4, 'title' => 'Votes:', 'value' => $data->votes->pluck('description')->implode("\n\n")]])
                ->map(fn($item) => (object) $item);
            }

            return $data;
        });

        if (!$bill) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bill not found',
                ],
                404,
            );
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json(
                [
                    'success' => true,
                    'bookmark' => false,
                    'vote_cast' => null,
                    'support_percentage' => 0,
                    'data' => $bill,
                ],
                200,
            );
        }

        $bookmark = Cache::remember("users_{$user->id}_bookmark_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return SavedBill::where('bill_url', $bill->bill_url)->where('user_id', $user->id)->first();
        });

        $votes = Cache::remember("users_{$user->id}_vote_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return BillVoteCast::where('bill_url', $bill->bill_url)->where('user_id', $user->id)->first();
        });

        $total_votes = Cache::remember("bill_vote_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return BillVoteCast::where('bill_url', $bill->bill_url)->get();
        });

        return response()->json(
            [
                'success' => true,
                'bookmark' => $bookmark ? (bool) $bookmark->is_saved : false,
                'vote_cast' => $votes ? ($votes->is_supported ? 'support' : 'oppose') : null,
                'support_percentage' => round($total_votes->count() > 0 ? ($total_votes->where('is_supported', 1)->count() / $total_votes->count()) * 100 : 0, 2),
                'data' => $bill,
            ],
            200,
        );
    }

    public function bookmarkBill(Request $request)
    {
        $user = Auth::user();
        Cache::forget("users_{$user->id}_bookmark_{$request->number}");

        $bill = Bill::where('number', $request->number)->first();
        if (!$bill) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bill not found',
                ],
                404,
            );
        }

        $bookmark = SavedBill::where('bill_url', $bill->bill_url)->where('user_id', $user->id)->first();

        if (!$bookmark) {
            SavedBill::create([
                'bill_url' => $bill->bill_url,
                'session' => $bill->session,
                'user_id' => $user->id,
                'is_saved' => true,
            ]);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Saved Bill Successfully',
                ],
                200,
            );
        }

        $bookmark->is_saved = $request->bookmark;
        $bookmark->save();

        return response()->json(
            [
                'success' => true,
                'message' => $request->bookmark ? 'Saved Bill Successfully' : 'Saved Bill Removed Successfully',
            ],
            200,
        );
    }

    public function supportBill(Request $request)
    {
        $user = Auth::user();
        Cache::forget("users_{$user->id}_vote_{$request->number}");
        Cache::forget("bill_vote_{$request->number}");

        $bill = Bill::where('id', $request->number)->first();
        if (!$bill) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bill not found',
                ],
                404,
            );
        }

        $is_supported = true;
        if ($request->support_type == 'oppose') {
            $is_supported = false;
        }

        $bookmark = BillVoteCast::where('bill_url', $bill->bill_url)->where('user_id', $user->id)->first();

        if (!$bookmark) {
            BillVoteCast::create([
                'bill_url' => $bill->bill_url,
                'session' => $bill->session,
                'user_id' => $user->id,
                'is_supported' => $is_supported,
            ]);

            $total_votes = BillVoteCast::where('bill_url', $bill->bill_url)->get();
            $percentage = ($total_votes->where('is_supported', 1)->count() / $total_votes->count()) * 100;

            return response()->json(
                [
                    'success' => true,
                    'support_percentage' => round($percentage, 2),
                    'vote_cast' => $is_supported ? 'support' : 'oppose',
                    'message' => 'Vote Cast Successfully',
                ],
                200,
            );
        }

        $bookmark->is_supported = $is_supported;
        $bookmark->save();
        $total_votes = BillVoteCast::where('bill_url', $bill->bill_url)->get();
        $percentage = ($total_votes->where('is_supported', 1)->count() / $total_votes->count()) * 100;

        return response()->json(
            [
                'success' => true,
                'support_percentage' => round($percentage, 2),
                'vote_cast' => $is_supported ? 'support' : 'oppose',
                'message' => 'Vote Cast Successfully',
            ],
            200,
        );
    }
}
