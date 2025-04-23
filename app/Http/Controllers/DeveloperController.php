<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeveloperController extends Controller
{
    public function login()
    {
        if(Auth::check()){
            return redirect()->intended('/log-viewer');
        }
        return view('login');
    }

    public function authenticate(Request $request){
        try {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            if(Auth::user()->role != '9908'){
                Auth::logout();
                return back();
            }

            return redirect()->intended('/log-viewer');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
        } catch (\Exception $th) {
            // dd($th->getMessage());
        }
    }

    // public function uploadDb(Request $request){
    //     $table = $request->table;
    //     $data = $request->data;

    //     DB::table($table)->insert($data);
    //     return response()->json(['message' => 'Data uploaded successfully']);
    // }
}
