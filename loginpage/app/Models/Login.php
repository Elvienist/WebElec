<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    protected $table = 'logins';
    protected $fillable = ['email', 'password', 'role', 'student_id'];
    protected $hidden = ['password'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}