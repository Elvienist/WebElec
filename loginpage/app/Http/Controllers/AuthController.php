<?php

namespace App\Http\Controllers;

use App\Models\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $login = Login::where('email', $request->email)->first();

        if ($login && Hash::check($request->password, $login->password)) {
            session([
                'login_id' => $login->id,
                'role' => $login->role,
                'student_id' => $login->student_id,
            ]);

            if ($login->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('portal.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login');
    }
}