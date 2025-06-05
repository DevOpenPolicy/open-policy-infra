<?php

namespace App\Http\Controllers\Web;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Jobs\CreateNewMpJob;
use App\Models\Bill;
use App\Models\Politicians;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use stdClass;

class HouseMentionController extends Controller
{
    private $openParliamentClass;
    public function __construct()
    {
        $this->openParliamentClass = new OpenParliamentClass();
    }
    public function getHouseMention(){
        $params = request('params');
        $politician = request('politician');


        $politician = explode('-', $politician);
        $politician_slug = $politician[0].'-'.$politician[1];
        $politician_detail = Politicians::where('politician_url',"LIKE", "%".$politician_slug."%")->first();
        if(!$politician_detail){
            $politician_slug = Str::title(str_replace('-', ' ', $politician_slug));
        }

        $url = "https://openparliament.ca$params?singlepage=1";
        $data = $this->openParliamentClass->getParliamentConversation($url);

        return response()->json([
            'success' => true,
            'data' => $data,
            'politician' => $politician_detail ? $politician_detail->name : $politician_slug,
            'number' => $politician[2],
        ]);
    }

    public function getBills(){
        $bill = request('bill') ?? null;

        $bill = '/bills/'.$bill;

        $bill = Bill::where('bill_url', $bill)->first();
        return response()->json(['id' => $bill->id]);
    }

    public function getVotes(){
        $vote = request('vote');

        // $data =  Cache::remember("votes_web_view_{$vote}", now()->addMinutes(3), function () use ($vote) {
            $politicians = Cache::remember('all_politicians_votes_section', now()->addDays(3), function () {
                return Politicians::select('politician_url','name','party_short_name')->get();
            });

            $temp = new stdClass();

            $data = $this->openParliamentClass->getPolicyInformation($vote);
            $temp->date = Carbon::parse($data['date'])->format('F jS, Y');
            $temp->vote_no = $data['number'];
            $temp->total_yes = $data['yea_total'];
            $temp->total_no = $data['nay_total'];
            
            
            $vote_summary = $this->openParliamentClass->getPolicyInformation($data['related']['ballots_url']."&limit=500");
            $data_summary = collect($vote_summary['objects'])
                ->transform(function ($item) use ($politicians) {
                    $data = [];
                    $politician = $politicians->firstWhere('politician_url', $item['politician_url']);
                    $data['politician_name'] = $politician ? $politician->name : null;
                    $data['party'] = $politician ? $politician->party_short_name : null;
                    $data['vote'] = $item['ballot'];
                    if(!$politician){
                        CreateNewMpJob::dispatch($item['politician_url']);
                        return null;
                    }

                    return $data;
                });

            $temp->yes_party = $data_summary->where('vote','Yes')->pluck('party')->unique()->values();
            $temp->no_party = $data_summary->where('vote','No')->pluck('party')->unique()->values();

            $summary = $data_summary->groupBy(function ($item) {
                return $item['party'];
            })
            ->all();
                
            
            $temp->summary = $summary;
        // });

        return response()->json([
            'success' => true,
            'data' => $temp
        ]);
    }
}
