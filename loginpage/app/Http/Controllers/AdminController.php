<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function students()
    {
        $students = Student::orderBy('last_name')->get();
        return view('admin.students', ['students' => $students]);
    }

    public function attendanceIndex(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $records = AttendanceRecord::with('student')
            ->where('attendance_date', $date)
            ->orderBy('attendance_time')
            ->get();

        return view('admin.attendance', ['records' => $records, 'date' => $date]);
    }

    public function markAbsent(Request $request)
    {
        $request->validate([
            'student_number' => 'required',
            'attendance_date' => 'required|date',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (!$student) {
            return back()->with('notfound', 'No student found with that student number.');
        }

        AttendanceRecord::updateOrCreate(
            ['student_id' => $student->id, 'attendance_date' => $request->attendance_date],
            ['status' => 'absent', 'attendance_time' => null]
        );

        return back()->with('success', 'Marked as absent.');
    }
}