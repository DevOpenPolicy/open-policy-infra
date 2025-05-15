<?php

namespace App\Http\Controllers\Web;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Debate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DebateController extends Controller
{
    private $openParliamentClass;
    public function __construct()
    {
        $this->openParliamentClass = new OpenParliamentClass();
    }
    public function getYearsData(){
        $startYear = 2000;
        $endYear = Debate::max('date') ? Carbon::parse(Debate::max('date'))->year : Carbon::now()->year;
        $debateYears = [];

        for ($year = $endYear; $year >= $startYear; $year--) {
            $debateYears[] = ['label' => $year, 'value' => $year];
        }

        return response()->json(['debate' => $debateYears]);
    }

    public function getYearDate(){
        $year = request('year') ?? 2024;
        $startMonth = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endMonth = Carbon::createFromDate($year, 12, 31)->endOfYear();

        $debateMonths = Debate::whereBetween('date', [$startMonth, $endMonth])
            ->select('date', 'id')
            ->orderBy('date', 'desc')
            ->get()
            ->transform(function ($query) {
                return [
                    'label' => Carbon::parse($query->date)->format('F jS'),
                    'value' => $query->id,
                ];
            });

        return response()->json(['dates' => $debateMonths]);
    }

    public function getDebateMentions($id = null){
        if($id == null){
            $year = Debate::max('date') ? Carbon::parse(Debate::max('date'))->year : Carbon::now()->year;
            $startMonth = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endMonth = Carbon::createFromDate($year, 12, 31)->endOfYear();

            $debateMention = Debate::whereBetween('date', [$startMonth, $endMonth])
                ->select('id','debate_url')
                ->orderBy('date', 'desc')
                ->first();

            $url = "https://openparliament.ca$debateMention->debate_url?singlepage=1";
            $data = $this->openParliamentClass->getParliamentConversation($url);
    
            return response()->json($data);
        }
        $debateMention = Debate::find($id);

        $url = "https://openparliament.ca$debateMention->debate_url?singlepage=1";
        $data = $this->openParliamentClass->getParliamentConversation($url);

        return response()->json($data);
    }
}
