<?php

namespace App\Http\Controllers\Web;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillVoteSummary;
use App\Models\ParliamentSession;
use App\Service\v1\BillClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function getBills(){
        $search = request('search');
        $bill_search = request('bill_search');
        $session_search = request('session_search')?: '45-1';

        
        $data = Cache::remember("web_bills_page_{$search}_{$bill_search}_{$session_search}", now()->addDays(7), function () use ($search, $bill_search,$session_search) {
            $bills = Bill::select('bills.id','bills.introduced','bills.number','bills.name','bills.short_name','bills.bills_json','politicians.name AS politician')
                ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where(function ($query) use ($search) {
                    $query
                        ->where('bills.name', 'like', "%{$search}%")
                        ->orWhere('bills.short_name', 'like', "%{$search}%")
                        ->orWhere('bills.number', 'like', "%{$search}%")
                        ->orWhere('politicians.name', 'like', "%{$search}%");
                })
                ->where('bills.session', $session_search)
                ->whereNotIn('bills.number', ['c-1', 's-1'])
                ->when(isset($bill_search), function ($query) use ($bill_search) {
                    $query->where('bills.is_government_bill', $bill_search);
                })
                ->get()
                ->transform(function ($query) {
                    $substring = 100;
                    return [
                        'id' => $query->id,
                        'billNumber' => $query->number,
                        'title' => strlen($query->name) > $substring ? substr($query->name, 0, $substring) . '...' : $query->name,
                        'subtitle' => strlen($query->short_name) > $substring ? substr($query->short_name, 0, $substring) . '...' : $query->short_name,
                        'date' => Carbon::parse($query->introduced)->format('Y-m-d'),
                        'status' => json_decode($query->bills_json,true)['bill_information']['status']['en'] === 'Law (royal assent given)' ? 'Law' : '',
                    ];
                });

            return [
                'success' => true,
                'bills' => $bills,
                'session' => ParliamentSession::select('name as label', 'session as value')->get()
            ];
        });

        return response()->json($data);

    }

    public function getBillSummary($id){
        $data = Cache::remember("web_one_bill_page_{$id}", now()->addDays(7), function () use ($id) {
            $data = Bill::select(
                'bills.*',
                'politicians.name as politician_name',
                'politicians.id as politician_id',
                'politicians.party_short_name as party_short_name'
            )
            ->join('politicians', 'bills.politician', '=', 'politicians.politician_url')
            ->where('bills.id', $id)
            ->first();
            
            if (!$data) {
                return null;
            }
            
            $data->bills_json = json_decode($data->bills_json);
            $data->summary = $data->summary ?: $this->billClass->getBillSummary($data->bill_url);
            $data->text_url = $data->bills_json->bill_information->text_url;
            $data->legisinfo_url = $data->bills_json->bill_information->legisinfo_url;
            $voteUrls = $data->bills_json->bill_information->vote_urls ?? [];
            
            $existingVotes = BillVoteSummary::whereIn('vote_url', $voteUrls)->get()->keyBy('vote_url');
            
            foreach ($voteUrls as $voteUrl) {
                if ($existingVotes->has($voteUrl)) {
                    continue;
                }
            
                $voteData = $this->openParliamentClass->getPolicyInformation($voteUrl);
            
                if (!$voteData) {
                    continue;
                }
            
                BillVoteSummary::create([
                    'bill_url' => $voteData['bill_url'] ?? '',
                    'session' => $voteData['session'] ?? '',
                    'description' => $voteData['description']['en'] ?? '',
                    'result' => $voteData['result'] ?? '',
                    'vote_url' => $voteData['url'] ?? $voteUrl,
                    'vote_json' => json_encode($voteData),
                ]);
            }
            
            $data->votes = BillVoteSummary::select('description', 'result','vote_json')
                ->whereIn('vote_url', $voteUrls)
                ->get()
                ->transform(function ($vote) {
                    $vote_json = json_decode($vote->vote_json, true);
                    return [
                        'description' => $vote->description,
                        'result' => $vote->result,
                        'date' => Carbon::parse($vote_json['date'])->format('M d Y'),
                    ];
                });
            
            return [
                'bill_number' => $data->number,
                'bill_type' => $data->is_government_bill ? 'Government' : 'Private Member',
                'name' => $data->name,
                'short_name' => $data->short_name,
                'sponsor' => $data->politician_name,
                'sponsor_party' => $data->party_short_name,
                'status' => $data->bills_json->bill_information->status->en,
                'summary' => $data->summary,
                'text_url' => $data->text_url,
                'legisinfo_url' => $data->legisinfo_url,
                'votes' => $data->votes,
            ];
        });

        return response()->json($data);
    
    }

    public function getBillHouseMention($id){
        $bill = Bill::find($id);

        if(!$bill){
            return response()->json([]);
        }

        $url = "https://openparliament.ca/bills/$bill->session/$bill->number/?singlepage=1";
        $data = $this->openParliamentClass->getParliamentConversation($url);

        return response()->json($data);
    }
}
