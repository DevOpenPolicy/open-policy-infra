<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppLinkController extends Controller
{
    public function activityLink()
    {
        $link = request('link');

        return response()->json([
            'success' => true,
            'data' => $link
            // 'https://openpolicy.me/',
        ]);
    }

    public function debateActivityLink()
    {
        $type = request('type');
        if ($type === 'debates_this_month') {
            return response()->json([
                'success' => true,
                'data' => 'https://openpolicy.me/',
            ]);
        } elseif ($type === 'debates_past') {
            return response()->json([
                'success' => true,
                'data' => 'https://openpolicy.me/',
            ]);
        } elseif ($type === 'debates_past') {
            return response()->json([
                'success' => true,
                'data' => 'https://openpolicy.me/',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => 'https://openpolicy.me/',
        ]);
    }

    public function committeeActivityLink()
    {
        $type = request('type');
        if ($type === 'current_committees') {
            return response()->json([
                'success' => true,
                'data' => 'https://openpolicy.me/',
            ]);
        } elseif ($type === 'recent_studies') {
            return response()->json([
                'success' => true,
                'data' => 'https://openpolicy.me/',
            ]);
        } 

        return response()->json([
            'success' => true,
            'data' => 'https://openpolicy.me/',
        ]);
    }
}
