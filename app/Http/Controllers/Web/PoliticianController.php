<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Opcodes\LogViewer\Facades\Cache;

class PoliticianController extends Controller
{
    public function getFormerPoliticians(){
        $search = request('search') ?? null;

        // $politicians = Cache::remember("web_search_politician_{$id}_{$province}_{$search}", now()->addDays(10), function () use ($id, $province, $search) {
            $politicians = \App\Models\Politicians::select('politicians.id', 'politicians.name', 'politicians.province_name as role','politician_provinces.name as province_name','politician_activity_logs.election_summary', 'politician_activity_logs.latest_activity as recent_activities','politician_activity_logs.activity as activity','politicians.politician_image',)
            ->where('politicians.is_former', true)
            ->join('politician_activity_logs', 'politician_activity_logs.politician_id', '=', 'politicians.id')
            ->join('politician_provinces', 'politician_provinces.short_name', '=', 'politicians.province_short_name')
            ->when($search, function ($query) use ($search) {
                return $query->where('politicians.name', 'like', '%' . $search . '%');
            })
            ->get()
            ->transform(function ($politician) {
                $politician->recent_activities = json_decode(json_decode($politician->recent_activities));
                $politician->activity = json_decode(json_decode($politician->activity));
                return $politician;
            });

            return [
                'politicians' => $politicians,
            ];

        // });
        
        return response()->json($politicians);
    }
    public function getPoliticians(){
        $id = request('id') ?? null;
        $province = request('province') ?? null;
        $search = request('search') ?? null;

        // $politicians = Cache::remember("web_search_politician_{$id}_{$province}_{$search}", now()->addDays(10), function () use ($id, $province, $search) {
            $politicians = \App\Models\Politicians::select('politicians.id', 'politicians.name', 'politicians.province_name as role','politician_provinces.name as province_name','politician_activity_logs.election_summary', 'politician_activity_logs.latest_activity as recent_activities','politician_activity_logs.activity as activity','politicians.politician_image',)
            ->join('politician_activity_logs', 'politician_activity_logs.politician_id', '=', 'politicians.id')
            ->join('politician_provinces', 'politician_provinces.short_name', '=', 'politicians.province_short_name')
            ->when(!$id, function ($query) {
                return $query->where('politicians.is_former', false);
            })
            ->when($province, function ($query) use ($province) {
                return $query->where('politicians.province_short_name', $province);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('politicians.name', 'like', '%' . $search . '%');
            })
            ->when($id, function ($query) use ($id) {
                return $query->where('politicians.id', $id);
            })
            ->get()
            ->transform(function ($politician) {
                $politician->recent_activities = json_decode(json_decode($politician->recent_activities));
                $politician->activity = json_decode(json_decode($politician->activity));
                return $politician;
            });

            return [
                'politicians' => $politicians,
                'provinces' => \App\Models\PoliticianProvince::select('name as label', 'short_name as value')->get(),
            ];

        // });
        
        return response()->json($politicians);
    }
}
