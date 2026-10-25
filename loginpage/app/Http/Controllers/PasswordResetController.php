<?php

namespace App\Http\Controllers;

use App\Models\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $login = Login::where('email', $request->email)->first();

        if ($login) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $login->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $link = route('password.reset', ['token' => $token, 'email' => $login->email]);

            Mail::raw("Reset your password using this link (valid for 60 minutes):\n\n$link", function ($m) use ($login) {
                $m->to($login->email)->subject('Reset your password');
            });
        }

        // Same message either way, so nobody can probe which emails exist.
        return back()->with('success', 'If that email has an account, a reset link has been sent.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        $valid = $row
            && Hash::check($data['token'], $row->token)
            && now()->diffInMinutes($row->created_at) < 60;

        $login = Login::where('email', $data['email'])->first();

        if (!$valid || !$login) {
            return back()->withErrors(['email' => 'This reset link is invalid or has expired.']);
        }

        $login->update(['password' => Hash::make($data['password'])]);
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('login')->with('success', 'Password updated. You can now log in.');
    }
}