<?php

namespace App\Http\Controllers\v1\Bills;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillVoteCast;
use App\Models\BillVoteSummary;
use App\Models\Politicians;
use App\Models\SavedBill;
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

    public function getAllBills(){
        // sorting by short_name, name, number, politician_name
        $search = request('search');
        $type = request('type');

        logger([$search, $type]);

        if($type == 'All Bills'){
            $type = null;
        }elseif($type == 'Private Member Bills'){
            $type = 0;
        }elseif($type == 'Government Bills'){
            $type = 1;
        }

        logger($type);

        $bills = Cache::remember("bills_page_{$search}_{$type}", now()->addDays(7), function () use ($search, $type) {
            return Bill::select('bills.introduced','bills.short_name','bills.name','bills.number','bills.is_government_bill','politicians.name as politician_name')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bills.session','44-1')
                ->whereNotIn('bills.number',['c-1','s-1'])
                ->where(function ($query) use ($search) {
                    $query->where('bills.name', 'like', "%{$search}%")
                        ->orWhere('bills.short_name', 'like', "%{$search}%")
                        ->orWhere('bills.number', 'like', "%{$search}%")
                        ->orWhere('politicians.name', 'like', "%{$search}%");
                })
                ->when(isset($type), function ($query) use ($type) {
                    return $query->where('bills.is_government_bill', $type);
                })
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $bills
        ], 200);
    }

    public function getBillNumber($number){
        $bill = Cache::remember("bill_{$number}", now()->addDays(7), function () use ($number) {
            $data = Bill::select('bills.*', 'politicians.name as politician_name')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bills.session', '44-1')
                ->where('bills.number', $number)
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
                    'bill_url'    => $voteData['bill_url'] ?? '',
                    'session'     => $voteData['session'] ?? '',
                    'description' => $voteData['description']['en'] ?? '',
                    'result'      => $voteData['result'] ?? '',
                    'vote_url'    => $voteData['url'] ?? $voteUrl,
                    'vote_json'   => json_encode($voteData),
                ]);
            }

            // Refresh with all vote summaries
            $data->votes = BillVoteSummary::select('vote_url', 'description', 'result')
                ->whereIn('vote_url', $voteUrls)
                ->get();

            if($data->votes->count() == 0){
                $data->app_summary = collect([
                    ['id' => 1, 'title' => 'Sponsor:', 'value' => $data->politician_name],
                    ['id' => 2, 'title' => 'Status:', 'value' => $data->bills_json->bill_information->status->en ?? 'Unknown'],
                    ['id' => 3, 'title' => 'Summary:', 'value' => $data->summary],
                ])->map(fn ($item) => (object) $item);
            }else{
                $data->app_summary = collect([
                    ['id' => 1, 'title' => 'Sponsor:', 'value' => $data->politician_name],
                    ['id' => 2, 'title' => 'Status:', 'value' => $data->bills_json->bill_information->status->en ?? 'Unknown'],
                    ['id' => 3, 'title' => 'Summary:', 'value' => $data->summary],
                    ['id' => 4, 'title' => 'Votes:', 'value' => $data->votes->pluck('description')->implode("\n\n")],
                ])->map(fn ($item) => (object) $item);
            }
                
            return $data;
        });

        

        if(!$bill){
            return response()->json([
                'success' => false,
                'message' => 'Bill not found'
            ], 404);
        }

        $user = Auth::user();
        $bookmark = Cache::remember("users_{$user->id}_bookmark_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return SavedBill::where('bill_url', $bill->bill_url)
            ->where('user_id', $user->id)    
            ->first();
        });

        $votes = Cache::remember("users_{$user->id}_vote_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return BillVoteCast::where('bill_url', $bill->bill_url)
                ->where('user_id', $user->id)    
                ->first();
        });

        $total_votes = Cache::remember("bill_vote_{$number}", now()->addDays(7), function () use ($bill, $user) {
            return BillVoteCast::where('bill_url', $bill->bill_url)->get();
        });

        return response()->json([
            'success' => true,
            'bookmark' => $bookmark ? (bool)$bookmark->is_saved : false,
            'vote_cast' => $votes ? ($votes->is_supported ? 'support' : 'oppose') : null,
            'support_percentage' => $total_votes->count() > 0 ? (($total_votes->where('is_supported', 1)->count()  / $total_votes->count()) * 100) : 0,
            'data' => $bill
        ], 200);
    } 

    public function bookmarkBill(Request $request){
        $user = Auth::user();
        Cache::forget("users_{$user->id}_bookmark_{$request->number}");
        
        $bill = Bill::where('number', $request->number)->first();
        if(!$bill){
            return response()->json([
                'success' => false,
                'message' => 'Bill not found'
            ], 404);
        }

        $bookmark = SavedBill::where('bill_url', $bill->bill_url)
            ->where('user_id', $user->id)    
            ->first();

        if(!$bookmark){
            SavedBill::create([
                'bill_url' => $bill->bill_url,
                'session' => $bill->session,
                'user_id' => $user->id,
                'is_saved' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saved Bill Successfully'
            ], 200);
        }

        $bookmark->is_saved = $request->bookmark;
        $bookmark->save();

        return response()->json([
            'success' => true,
            'message' => $request->bookmark ? 'Saved Bill Successfully' : 'Saved Bill Removed Successfully'
        ], 200);
    }

    public function supportBill(Request $request){
        $user = Auth::user();
        Cache::forget("users_{$user->id}_vote_{$request->number}");
        Cache::forget("bill_vote_{$request->number}");

        $bill = Bill::where('number', $request->number)->first();
        if(!$bill){
            return response()->json([
                'success' => false,
                'message' => 'Bill not found'
            ], 404);
        }

        $is_supported = true;
        if($request->support_type == 'oppose'){
            $is_supported = false;
        }

        $bookmark = BillVoteCast::where('bill_url', $bill->bill_url)
            ->where('user_id', $user->id)    
            ->first();

        if(!$bookmark){
            BillVoteCast::create([
                'bill_url' => $bill->bill_url,
                'session' => $bill->session,
                'user_id' => $user->id,
                'is_supported' => $is_supported,
            ]);
            
            $total_votes = BillVoteCast::where('bill_url', $bill->bill_url)->get();
            $percentage = $total_votes->where('is_supported', 1)->count()  / $total_votes->count() * 100;

            return response()->json([
                'success' => true,
                'support_percentage' => $percentage,
                'vote_cast' => $is_supported ? 'support' : 'oppose',
                'message' => 'Vote Cast Successfully'
            ], 200);
        }

        $bookmark->is_supported = $is_supported;
        $bookmark->save();
        $total_votes = BillVoteCast::where('bill_url', $bill->bill_url)->get();
        $percentage = ($total_votes->where('is_supported', 1)->count()  / $total_votes->count()) * 100;

        return response()->json([
            'success' => true,
            'support_percentage' => $percentage,
            'vote_cast' => $is_supported ? 'support' : 'oppose',
            'message' => 'Vote Cast Successfully'
        ], 200);

    }
}
