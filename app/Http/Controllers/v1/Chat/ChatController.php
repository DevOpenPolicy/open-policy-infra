<?php

namespace App\Http\Controllers\v1\Chat;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Politicians;
use App\Models\RepresentativeIssue;
use App\Models\AceChatSession;
use App\Models\AceChatMessage;
use App\Service\v1\BillClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

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
            'session_id' => 'nullable|integer'
        ]);

        $sessionId = $validated['session_id'] ?? null;
        $messages = $validated['messages'];
        $lastMessage = end($messages);

        if($this->chat_system == 'open_ai'){
            $open_ai = new OpenAiController();
            $response = $open_ai->generateAceResponse($validated);
            
            // If the response is a JsonResponse, we can extract data
            if (method_exists($response, 'getData')) {
                $responseData = $response->getData(true);
                $reply = $responseData['reply'] ?? null;

                // Save to history if user is authenticated and we have a reply
                if (Auth::check() && $reply) {
                    if (!$sessionId) {
                        $session = AceChatSession::create([
                            'user_id' => Auth::id(),
                            'title' => substr($lastMessage['content'], 0, 50) . (strlen($lastMessage['content']) > 50 ? '...' : '')
                        ]);
                        $sessionId = $session->id;
                    } else {
                        $session = AceChatSession::find($sessionId);
                        if ($session) {
                            $session->touch(); // Update updated_at
                        } else {
                            // If session_id was provided but not found, create a new one
                            $session = AceChatSession::create([
                                'user_id' => Auth::id(),
                                'title' => substr($lastMessage['content'], 0, 50) . (strlen($lastMessage['content']) > 50 ? '...' : '')
                            ]);
                            $sessionId = $session->id;
                        }
                    }

                    // Save user message
                    AceChatMessage::create([
                        'ace_chat_session_id' => $sessionId,
                        'role' => 'user',
                        'content' => $lastMessage['content']
                    ]);

                    // Save assistant message
                    AceChatMessage::create([
                        'ace_chat_session_id' => $sessionId,
                        'role' => 'assistant',
                        'content' => $reply
                    ]);
                    
                    $responseData['session_id'] = $sessionId;
                    return response()->json($responseData);
                }
            }

            return $response;
        }
    }

    public function aiSearch(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        $lastMessage = end($validated['messages']);
        $query = $lastMessage['content'];

        try {
            $chatGpt = new \App\Service\v1\ChatGptClass();
            $keywords = $chatGpt->extractSearchTerms($query);

            $bills = Bill::select('bills.id', 'bills.introduced', 'bills.short_name', 'bills.name', 'bills.number', 'bills.is_government_bill', 'bills.session', 'politicians.name as politician_name')
                ->leftJoin('politicians', 'bills.politician', '=', 'politicians.politician_url')
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('bills.name', 'like', "%{$keyword}%")
                          ->orWhere('bills.short_name', 'like', "%{$keyword}%")
                          ->orWhere('bills.number', 'like', "%{$keyword}%");
                    }
                })
                ->where('bills.session', '45-1') // Defaulting to current session
                ->limit(5)
                ->get();

            $reply = $chatGpt->generateSearchResponse($query, $bills);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'bills' => $bills
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Search Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the AI search. Please try again later.'
            ], 500);
        }
    }

    public function aiSearchMembers(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
        ]);

        $lastMessage = end($validated['messages']);
        $query = $lastMessage['content'];

        try {
            $chatGpt = new \App\Service\v1\ChatGptClass();
            $keywords = $chatGpt->extractMemberSearchTerms($query);

            $members = Politicians::select('politicians.id', 'politicians.name', 'politicians.party_name', 'politicians.province_name', 'politicians.politician_image')
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('politicians.name', 'like', "%{$keyword}%")
                          ->orWhere('politicians.party_name', 'like', "%{$keyword}%")
                          ->orWhere('politicians.province_name', 'like', "%{$keyword}%");
                    }
                })
                ->limit(5)
                ->get();

            $reply = $chatGpt->generateMemberSearchResponse($query, $members);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'members' => $members
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Member Search Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the AI search. Please try again later.'
            ], 500);
        }
    }
}
