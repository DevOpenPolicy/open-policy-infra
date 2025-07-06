<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppLinkController extends Controller
{
    const LINK_URL = 'https://app.openpolicy.me/';
    // const LINK_URL = 'http://localhost:5173/';
    public function activityLink()
    {
        $link = request('link');

        $link = ltrim($link, '/');
        if (array_filter(['bills', 'debate', 'committees', 'votes'], fn($keyword) => str_contains($link, $keyword))) {
            return response()->json([
                'success' => true,
                'data' => self::LINK_URL."$link",
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => self::LINK_URL,
        ]);
    }

    public function debateActivityLink()
    {
        return response()->json([
            'success' => true,
            'data' => self::LINK_URL.'debates',
        ]);
        // $type = request('type');
        // if ($type === 'debates_this_month') {
        //     return response()->json([
        //         'success' => true,
        //         'data' => self::LINK_URL.'/debates',
        //     ]);
        // } elseif ($type === 'debates_past') {
        //     return response()->json([
        //         'success' => true,
        //         'data' => 'https://app.openpolicy.me/debates',
        //     ]);
        // } elseif ($type === 'debates_past') {
        //     return response()->json([
        //         'success' => true,
        //         'data' => 'https://app.openpolicy.me/debates',
        //     ]);
        // }

        // return response()->json([
        //     'success' => true,
        //     'data' => 'https://app.openpolicy.me/debates',
        // ]);
    }

    public function committeeActivityLink()
    {
        return response()->json([
            'success' => true,
            'data' => self::LINK_URL.'committees',
        ]);

        // $type = request('type');
        // if ($type === 'current_committees') {
        //     return response()->json([
        //         'success' => true,
        //         'data' => 'https://app.openpolicy.me/committees',
        //     ]);
        // } elseif ($type === 'recent_studies') {
        //     return response()->json([
        //         'success' => true,
        //         'data' => 'https://app.openpolicy.me/committees',
        //     ]);
        // } 

        // return response()->json([
        //     'success' => true,
        //     'data' => 'https://app.openpolicy.me/committees',
        // ]);
    }
}
