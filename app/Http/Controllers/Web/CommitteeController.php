<?php

namespace App\Http\Controllers\Web;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\CommitteeYearLog;
use App\Models\CommitteeYearLogData;

class CommitteeController extends Controller
{
    private $openParliamentClass;
    public function __construct()
    {
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function committeeTopics(){
        return response()->json(['data' => Committee::select('name as label', 'id as value')->get()]);
    }

    public function getYear(){
        $id = request('id') ?? 1;


        $data = CommitteeYearLog::select('year as label', 'id as value')
            ->where('committee_id', $id)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function getYearDate($id){

        $data = CommitteeYearLogData::select('date as label', 'id as value')
            ->where('committee_year_log_id', $id)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function houseMentions($id = null){
        if($id == null){
            $year = CommitteeYearLog::max('year') ? CommitteeYearLog::max('year') : now()->year;
            $id = CommitteeYearLog::where('year', $year)->first()->id;
        }

        $data = CommitteeYearLogData::where('id', $id)->first();
        
        if(!$data){
            return response()->json(['data' => []]);
        }

        $url = "https://openparliament.ca$data->url?singlepage=1";
        $data = $this->openParliamentClass->getParliamentConversation($url);
        return response()->json(['data' => $data]);

    }
}
