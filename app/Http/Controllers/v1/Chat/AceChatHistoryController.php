<?php

namespace App\Http\Controllers\v1\Chat;

use App\Http\Controllers\Controller;
use App\Models\AceChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AceChatHistoryController extends Controller
{
    public function index()
    {
        $sessions = AceChatSession::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function show($id)
    {
        $session = AceChatSession::with('messages')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    public function destroy($id)
    {
        $session = AceChatSession::where('user_id', Auth::id())
            ->findOrFail($id);
            
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }
}
