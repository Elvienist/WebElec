<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = ['student_id', 'attendance_date', 'attendance_time', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}