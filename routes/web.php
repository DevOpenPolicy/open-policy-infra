<?php

use App\GenerateContentClass;
use App\Helper\OpenParliamentClass;
use App\Http\Controllers\DeveloperController;
use App\Jobs\SystemSetUp;
use App\Models\Bill;
use App\Models\Committee;
use App\Models\CommitteeYearLog;
use App\Models\ParliamentSession;
use App\Models\PoliticianActivityLog;
use App\Models\Politicians;
use App\Models\User;
use App\Service\v1\BillClass;
use App\Service\v1\CommitteeClass;
use App\Service\v1\DebateClass;
use App\Service\v1\RepresentativeClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Symfony\Component\DomCrawler\Crawler;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/developer-ops/login', [DeveloperController::class, 'login']);
Route::post('/dev-ops/authenticate', [DeveloperController::class, 'authenticate'])->name('dev.authenticate');

Route::get('/testing/{table}', function ($table) {
    // $table = $table ?? 'bills';
    // return DB::table($table)->limit(100)->get();
});
Route::get('/counts', function () {
    
});




