<?php

use App\Jobs\SystemSetUp;
use App\Models\Politicians;
use App\Models\User;
use App\Service\v1\BillClass;
use App\Service\v1\CommitteeClass;
use App\Service\v1\DebateClass;
use App\Service\v1\RepresentativeClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/all-database-view/{table}', function ($table) {
    dd(DB::table($table)->limit(10)->orderBy('id','DESC')->get());
});

Route::get('/testing', function () {
    return view('login');
    // return User::all();
    // $id = 5;
    // $user = User::find($id);

    // $name = $user->first_name." ".$user->last_name;
    // $pol =  Politicians::where('name', $name)->get();
    // if($pol){
    //     $user->role = '232';
    //     $user->save();
    // }

    // dd('done');

    // dd((new BillClass())->getBillSummary('https://www.parl.ca/legisinfo/en/bill/44-1/C-2'));

    SystemSetUp::dispatch();
});




