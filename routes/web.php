<?php

use App\Service\v1\RepresentativeClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/all-database', function () {
    $query = "
    SELECT tablename 
    FROM pg_tables 
    WHERE schemaname NOT IN ('pg_catalog', 'information_schema') 
    ORDER BY tablename
";
    $tables = DB::select($query);
    $data = [];
    foreach ($tables as $table) {
        
        $data[] = [$table->tablename, DB::table($table->tablename)->count()];
        //drop tables that the count is zero
        // if(DB::table($table->tablename)->count() == 0){
        //     DB::table($table->tablename)->truncate();
        // }
    }

    return $data;
});

Route::get('/all-database-view/{table}', function ($table) {
    dd(DB::table($table)->limit(10)->orderBy('id','DESC')->get());
});

Route::get('/testing', function () {
    // return Cache::get('/politicians/ziad-aboultaif/');
    return $data = (new RepresentativeClass())->searchRepresentative('stephen');

    
    dd( (new RepresentativeClass())->getRepresentatives($data));
});




