<?php

namespace App\Http\Controllers\v1\Chat;

use App\Http\Controllers\Controller;
use App\Service\v1\ChatGptClass;
use Illuminate\Http\Request;

class OpenAiController extends Controller
{
    private $chatGptClass;
    public function __construct(){
        $this->chatGptClass = new ChatGptClass();
    }
    public function generateBillResponse($request)
    {
        $billNumber = $request['bill_number'];
        $summary = $request['summary'];
        $instruction = $request['instruction'] ?? '';

        $response = $this->chatGptClass->generateBillResponse($billNumber, $summary, $instruction);

        return response()->json([
            'response' => $response
        ]);
    }


    public function generateIssueResponse($request)
    {
        $summary = $request['summary'];
        $instruction = $request['instruction'] ?? '';

        $response = $this->chatGptClass->generateIssueResponse($summary, $instruction);

        return response()->json([
            'response' => $response
        ]);
    }
}
