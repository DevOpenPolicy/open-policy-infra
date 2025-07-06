<?php

namespace App\Http\Controllers\v1\MP;

use App\Helper\OpenParliamentClass;
use App\Http\Controllers\Controller;
use App\Models\IssueVoteCast;
use App\Models\Politicians;
use App\Models\RepresentativeIssue;
use App\Models\SavedIssue;
use App\Models\User;
use App\RoleManager;
use App\Service\v1\RepresentativeClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RepresentativeController extends Controller
{
    private $representativeClass;
    private $openParliamentClass;
    public function __construct()
    {
        $this->representativeClass = new RepresentativeClass();
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function checkRepPostalCodeInformationIsCached($postal_code){
        $key = Cache::has('representative_postal_code_' . $postal_code);
        if($key){
            return Cache::get('representative_postal_code_' . $postal_code); 
        }

        return $this->getUserRepresentative($postal_code)->getData()->data;
    }

    public function getUserRepresentative($postal_code = null)
    {
        if(!$postal_code){
            $user = Auth::user();
            $postal_code = $user->postal_code;
        }

        $data = Cache::remember("representative_postal_code_{$postal_code}", now()->addDays(7), function () use ($postal_code) {
            $representative = $this->openParliamentClass->getPolicyInformation('/search?q=' . $postal_code);

            $data = new \stdClass();
            $data->name = $representative['name'];
            $data->role = $this->representativeClass->getRepresentativesRole($representative);
            $data->image = $this->representativeClass->getRepresentativesImage($representative);
            $data->email = $representative['email'] ?? '';
            $politician = Politicians::where('email', $data->email)->first();
            $data->phone = $representative['voice'] ?? '';
            $data->office = $this->representativeClass->getRepresentativeAddress($representative['other_info']['constituency_offices'][0] ?? '');
            $recent_activities = $this->representativeClass->getActivityLog($politician);
            $data->vote_activity = $recent_activities['vote_activity'];
            $data->house_activity = $recent_activities['house_activity'];


            $names = explode(' ', $representative['name']);

            $data->issues = RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->where('representative_issues.status','approved')
                ->select('representative_issues.name', 'representative_issues.summary', 'representative_issues.created_at as date', 'representative_issues.id')
                ->where(function ($query) use ($names) {
                    if (count($names) === 2) {
                        $query->where('users.first_name', 'LIKE', '%' . $names[0] . '%')->where('users.last_name', 'LIKE', '%' . $names[1] . '%');
                    } else {
                        $query->where('users.first_name', 'LIKE', '%' . $names . '%')->orWhere('users.last_name', 'LIKE', '%' . $names . '%');
                    }
                })
                ->where('users.role', RoleManager::REPRESENTATIVE)
                ->get();

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function showRepresentatives()
    {
        try{
        $search = request('search');

        $data = Cache::remember("search_representative_by_{$search}", now()->addDays(7), function () use ($search) {
            return Politicians::select('name', 'province_name', 'id')
                ->where('is_former',false)
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getRepresentative()
    {
        $id = request('id');
        $data = Cache::remember("get_representative by_id_{$id}", now()->addDays(7), function () use ($id) {

            $politician = Politicians::where('id', $id)->first();
            $representative = json_decode($politician->politician_json, true);


            $data = new \stdClass();
            $data->name = $politician->name;
            $data->role = $politician->province_name;
            $data->office = $this->representativeClass->getRepresentativeAddress($politician->constituency_offices);
            $data->image = $politician->politician_image;
            $data->email = $politician->email;
            $data->phone = $politician->voice;
            $recent_activities = $this->representativeClass->getActivityLog($politician);
            $data->vote_activity = $recent_activities['vote_activity'];
            $data->house_activity = $recent_activities['house_activity'];

            $names = explode(' ', $representative['name']);

            $data->issues = RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->where('representative_issues.status','approved')
                ->select('representative_issues.name', 'representative_issues.summary', 'representative_issues.created_at as date', 'representative_issues.id')
                ->where(function ($query) use ($names) {
                    if (count($names) === 2) {
                        $query->where('users.first_name', 'LIKE', '%' . $names[0] . '%')->where('users.last_name', 'LIKE', '%' . $names[1] . '%');
                    } else {
                        $query->where('users.first_name', 'LIKE', '%' . $names[0] . '%')->orWhere('users.last_name', 'LIKE', '%' . $names[0] . '%');
                    }
                })
                ->where('users.role', RoleManager::REPRESENTATIVE)
                ->get();

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]); $data;
    }

    public function getIssue(){
        $id = request('id');
        $data = Cache::remember("get__rep_issue_by_id_{$id}", now()->addDays(7), function () use ($id) {
            $issue = RepresentativeIssue::join('users', 'representative_issues.representative_id', '=', 'users.id')
                ->where('representative_issues.status','approved')
                ->select('representative_issues.*','users.first_name', 'users.last_name')    
                ->where('representative_issues.id', $id)
                ->first();

            $data = new \stdClass();
            $data->id = $issue->id;
            $data->name = $issue->name;
            $data->summary =$issue->description;

            $data->app_summary = collect([
                ['id' => 1, 'title' => 'Sponsor:', 'value' => $issue->first_name." ".$issue->last_name],
                ['id' => 2, 'title' => 'Summary:', 'value' => $issue->summary],
                ['id' => 3, 'title' => 'Description:', 'value' => $issue->description],
            ])->map(fn ($item) => (object) $item);

            return $data;
        });

        $user = Auth::user();

        if(!$user){
            return response()->json([
                'success' => true,
                'bookmark' => false,
                'vote_cast' => null,
                'support_percentage' => 0,
                'data' => $data
            ], 200);
        }
        
        $bookmark = Cache::remember("users_{$user->id}_bookmark_issue_{$id}", now()->addDays(7), function () use ($data, $user) {
            return SavedIssue::where('issue_id', $data->id)
            ->where('user_id', $user->id)    
            ->first();
        });

        $votes = Cache::remember("users_{$user->id}_issue_vote_{$id}", now()->addDays(7), function () use ($data, $user) {
            return IssueVoteCast::where('issue_id', $data->id)
                ->where('user_id', $user->id)    
                ->first();
        });

        $total_votes = Cache::remember("issue_vote_{$id}", now()->addDays(7), function () use ($data, $user) {
            return IssueVoteCast::where('issue_id', $data->id)->get();
        });

        return response()->json([
            'success' => true,
            'bookmark' => $bookmark ? (bool)$bookmark->is_saved : false,
            'vote_cast' => $votes ? ($votes->is_supported ? 'support' : 'oppose') : null,
            'support_percentage' => round($total_votes->count() > 0 ? (($total_votes->where('is_supported', 1)->count()  / $total_votes->count()) * 100) : 0,2),
            'data' => $data
        ], 200);
    }

    public function bookmarkIssue(Request $request){
        $user = Auth::user();
        Cache::forget("users_{$user->id}_bookmark_issue_{$request->id}");
        
        $issue = RepresentativeIssue::where('id', $request->id)->first();
        if(!$issue){
            return response()->json([
                'success' => false,
                'message' => 'Issue not found'
            ], 404);
        }

        $bookmark = SavedIssue::where('issue_id', $issue->id)
            ->where('user_id', $user->id)    
            ->first();

        if(!$bookmark){
            SavedIssue::create([
                'issue_id' => $issue->id,
                'user_id' => $user->id,
                'is_saved' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saved Issue Successfully'
            ], 200);
        }

        $bookmark->is_saved = $request->bookmark;
        $bookmark->save();

        return response()->json([
            'success' => true,
            'message' => $request->bookmark ? 'Saved Issue Successfully' : 'Saved Issue Removed Successfully'
        ], 200);
    }

    public function supportIssue(Request $request){
        $user = Auth::user();
        Cache::forget("users_{$user->id}_issue_vote_{$request->number}");
        Cache::forget("issue_vote_{$request->number}");

        $issue = RepresentativeIssue::where('id', $request->id)->first();
        if(!$issue){
            return response()->json([
                'success' => false,
                'message' => 'Issue not found'
            ], 404);
        }

        $is_supported = true;
        if($request->support_type == 'oppose'){
            $is_supported = false;
        }

        $bookmark = IssueVoteCast::where('issue_id', $issue->id)
            ->where('user_id', $user->id)    
            ->first();

        if(!$bookmark){
            IssueVoteCast::create([
                'issue_id' => $issue->id,
                'user_id' => $user->id,
                'is_supported' => $is_supported,
            ]);
            
            $total_votes = IssueVoteCast::where('issue_id', $issue->id)->get();
            $percentage = $total_votes->where('is_supported', 1)->count()  / $total_votes->count() * 100;

            return response()->json([
                'success' => true,
                'support_percentage' => round($percentage,2),
                'vote_cast' => $is_supported ? 'support' : 'oppose',
                'message' => 'Vote Cast Successfully'
            ], 200);
        }

        $bookmark->is_supported = $is_supported;
        $bookmark->save();
        $total_votes = IssueVoteCast::where('issue_id', $issue->id)->get();
        $percentage = ($total_votes->where('is_supported', 1)->count()  / $total_votes->count()) * 100;

        return response()->json([
            'success' => true,
            'support_percentage' => round($percentage,2),
            'vote_cast' => $is_supported ? 'support' : 'oppose',
            'message' => 'Vote Cast Successfully'
        ], 200);

    }

    public function activityLink(){
        $link = request('link');

        return response()->json([
            'success' => true,
            'data' => 'https://openpolicy.me/',
        ]);
    }
}
