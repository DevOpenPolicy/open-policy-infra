<?php

namespace App\Http\Controllers\Web;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Politicians;
use Illuminate\Http\Request;

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

        $url = "https://openparliament.ca$params?singlepage=1";
        $data = $this->openParliamentClass->getParliamentConversation($url);

        return response()->json([
            'success' => true,
            'data' => $data,
            'politician' => $politician_detail->name,
            'number' => $politician[2],
        ]);
    }

    public function getBills(){
        $bill = request('bill') ?? null;

        $bill = '/bills/'.$bill;

        $bill = Bill::where('bill_url', $bill)->first();
        return response()->json(['id' => $bill->id]);
    }
}
