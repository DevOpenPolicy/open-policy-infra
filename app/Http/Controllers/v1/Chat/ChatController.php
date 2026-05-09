<?php

namespace App\Http\Controllers\v1\Chat;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\RepresentativeIssue;
use App\Service\v1\BillClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    private $billClass;
    private $chat_system;

    public function __construct(){
        $this->billClass = new BillClass();
        $this->chat_system = 'open_ai';
    }

    public function getBillInformation(){
        $number = request('bill_number');


        $chat_info = Cache::remember("chat_bill_{$number}", now()->addDays(7), function () use ($number) {
            $data = Cache::get("bill_{$number}");
            if($data) return $data;


            $data = Bill::select('bills.id', 'bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'bills.session', 'bills.bill_url', 'politicians.name as politician_name')
                ->leftJoin('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where('bills.number', $number)
                ->first();

            if($data){
                $data->summary = $this->billClass->getBillSummary($data->bill_url);
            }
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $chat_info
        ], 200);
    }

    public function getBillInformationById(){
        $id = request('bill_id');


        $chat_info = Cache::remember("chat_bill_id_{$id}", now()->addDays(7), function () use ($id) {
            $data = Cache::get("bill_id_{$id}");
            if($data) return $data;


            $data = Bill::select('bills.id', 'bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'bills.session', 'bills.bill_url', 'politicians.name as politician_name')
                ->leftJoin('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where(function($query) use ($id) {
                    $query->where('bills.id', $id)
                          ->orWhere('bills.number', $id);
                })
                ->first();

            if($data){
                $data->summary = $this->billClass->getBillSummary($data->bill_url);
            }
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $chat_info
        ], 200);
    }

    public function getIssueInformation(){
        $id = request('id');

        $chat_info = Cache::remember("chat_get__rep_issue_by_id_{$id}", now()->addDays(7), function () use ($id) {
            $data = Cache::get("get__rep_issue_by_id_{$id}");
            if($data) return $data;


            $data = RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->select('representative_issues.*','users.first_name', 'users.last_name')    
                ->where('representative_issues.id', $id)
                ->first();

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $chat_info
        ], 200);
    }

    public function billChat(Request $request){
        $validated = $request->validate([
            'bill_number' => 'required|string|max:20',
            'summary' => 'required|string',
            'instruction' => 'nullable|string|max:500',
        ]);

        if($this->chat_system == 'open_ai'){
            $open_ai = new OpenAiController();
            return $open_ai->generateBillResponse($validated);
        }
    }

    public function billChatLink(Request $request){

        $validated = $request->validate([
            'link' => 'required|string|max:500',
            'instruction' => 'nullable|string|max:500',
        ]);

        if($this->chat_system == 'open_ai'){
            $open_ai = new OpenAiController();
            return $open_ai->generateBillResponseForLink($validated);
        }
    }

    public function issueChat(Request $request){
        $validated = $request->validate([
            'id' => 'required|string|max:20',
            'summary' => 'required|string',
            'instruction' => 'nullable|string|max:500',
        ]);

        if($this->chat_system == 'open_ai'){
            $open_ai = new OpenAiController();
            return $open_ai->generateIssueResponse($validated);
        }
    }

    public function aceChat(Request $request){
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        if($this->chat_system == 'open_ai'){
            $open_ai = new OpenAiController();
            return $open_ai->generateAceResponse($validated);
        }
    }
}
