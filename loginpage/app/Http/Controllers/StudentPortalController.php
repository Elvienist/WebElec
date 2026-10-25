<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AttendanceRecord;

class StudentPortalController extends Controller
{
    public function dashboard()
    {
        $student = Student::find(session('student_id'));
        $records = AttendanceRecord::where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->take(5)
            ->get();

        return view('portal.dashboard', ['student' => $student, 'records' => $records]);
    }

    public function history()
    {
        $student = Student::find(session('student_id'));
        $records = AttendanceRecord::where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->get();

        return view('portal.history', ['student' => $student, 'records' => $records]);
    }

    public function absences()
    {
        $student = Student::find(session('student_id'));
        $records = AttendanceRecord::where('student_id', $student->id)
            ->where('status', 'absent')
            ->orderByDesc('attendance_date')
            ->get();

        return view('portal.absences', ['student' => $student, 'records' => $records]);
    }
}