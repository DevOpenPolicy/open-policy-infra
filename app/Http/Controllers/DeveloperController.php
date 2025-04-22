<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeveloperController extends Controller
{
    public function login()
    {
        User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'devops@open_policy.com',
            'password' => bcrypt('123456789!@$asdf'),
            'role' => '9908',
            'phone' => '08162076900',
            'postal_code' => '10001',
        ]);
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

            $request->session()->regenerate();
            return redirect()->intended('/log-viewer');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
        } catch (\Exception $th) {
            // dd($th->getMessage());
        }
    }
}
