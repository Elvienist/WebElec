<?php

namespace App\Http\Controllers;

use App\Models\Login;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SignupController extends Controller
{
    public function show()
    {
        return view('auth.signup');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|in:student,admin',
            'email' => 'required|email|unique:logins,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'student_number' => 'required_if:role,student|nullable|string',
            'admin_code' => 'required_if:role,admin|nullable|string',
        ]);

        $studentId = null;

        if ($data['role'] === 'admin') {
            $secret = config('app.admin_signup_code');

            if (!$secret || !hash_equals($secret, $data['admin_code'])) {
                return back()->withErrors(['admin_code' => 'Invalid admin code.'])->withInput();
            }
        } else {
            $student = Student::where('student_number', $data['student_number'])->first();

            if (!$student) {
                return back()->withErrors([
                    'student_number' => 'No student found with that number. Ask an admin to register you first.',
                ])->withInput();
            }

            if (Login::where('student_id', $student->id)->exists()) {
                return back()->withErrors([
                    'student_number' => 'This student already has an account.',
                ])->withInput();
            }

            $studentId = $student->id;
        }

        Login::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'student_id' => $studentId,
        ]);

        return redirect()->route('login')->with('success', 'Account created. You can now log in.');
    }
}