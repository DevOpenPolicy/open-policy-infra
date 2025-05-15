<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppLinkController extends Controller
{
    public function activityLink()
    {
        $link = request('link');

        if (array_filter(['bills', 'debate', 'committees'], fn($keyword) => str_contains($link, $keyword))) {
            return response()->json([
                'success' => true,
                'data' => "https://app.openpolicy.me/$link",
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => "https://openpolicy.me",
        ]);
    }

    public function debateActivityLink()
    {
        $type = request('type');
        if ($type === 'debates_this_month') {
            return response()->json([
                'success' => true,
                'data' => 'https://app.openpolicy.me/debates',
            ]);
        } elseif ($type === 'debates_past') {
            return response()->json([
                'success' => true,
                'data' => 'https://app.openpolicy.me/debates',
            ]);
        } elseif ($type === 'debates_past') {
            return response()->json([
                'success' => true,
                'data' => 'https://app.openpolicy.me/debates',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => 'https://app.openpolicy.me/debates',
        ]);
    }

    public function committeeActivityLink()
    {
        $type = request('type');
        if ($type === 'current_committees') {
            return response()->json([
                'success' => true,
                'data' => 'https://app.openpolicy.me/committees',
            ]);
        } elseif ($type === 'recent_studies') {
            return response()->json([
                'success' => true,
                'data' => 'https://app.openpolicy.me/committees',
            ]);
        } 

        return response()->json([
            'success' => true,
            'data' => 'https://app.openpolicy.me/committees',
        ]);
    }
}
