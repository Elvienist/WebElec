<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard()
    {
        return view('admin.scan');
    }

    public function search(Request $request)
    {
        $request->validate(['student_number' => 'required']);

        $student = Student::where('student_number', $request->student_number)->first();

        if (!$student) {
            return back()->with('notfound', 'No student found with that student number.');
        }

        $today = now()->format('Y-m-d');

        $alreadyLogged = AttendanceRecord::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$alreadyLogged) {
            AttendanceRecord::create([
                'student_id' => $student->id,
                'attendance_date' => $today,
                'attendance_time' => now()->format('H:i:s'),
                'status' => 'present',
            ]);
        }

        return view('admin.scan-result', ['student' => $student]);
    }

    public function create()
    {
        return view('admin.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_number' => 'required|unique:students,student_number',
            'first_name' => 'required',
            'last_name' => 'required',
            'year_section' => 'required',
            'course' => 'required',
            'picture' => 'nullable|image|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('students', 'public');
        }

        Student::create([
            'student_number' => $request->student_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'year_section' => $request->year_section,
            'course' => $request->course,
            'picture' => $path,
        ]);

        return redirect()->route('admin.students')->with('success', 'Student registered successfully.');
    }
}