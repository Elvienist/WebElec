<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentPortalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ADMIN ROUTES
Route::middleware(['checklogin', 'isadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/scan', [StudentController::class, 'dashboard'])->name('scan');
    Route::post('/scan/search', [StudentController::class, 'search'])->name('scan.search');

    Route::get('/students', [AdminController::class, 'students'])->name('students');
    Route::get('/students/register', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students/register', [StudentController::class, 'store'])->name('students.store');

    Route::get('/attendance', [AdminController::class, 'attendanceIndex'])->name('attendance');
    Route::post('/attendance/mark-absent', [AdminController::class, 'markAbsent'])->name('attendance.markAbsent');
});

// STUDENT PORTAL ROUTES
Route::middleware(['checklogin', 'isstudent'])->prefix('student')->name('portal.')->group(function () {
    Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/history', [StudentPortalController::class, 'history'])->name('history');
    Route::get('/absences', [StudentPortalController::class, 'absences'])->name('absences');
});