<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Attendance</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
    <div class="card text-center">
        <h2>Student Attendance</h2>
        <p class="subtitle">Enter the student number and press Enter.</p>

        <form action="{{ route('admin.scan.search') }}" method="POST">
            @csrf
            <input type="text" name="student_number" placeholder="Enter Student Number" autofocus>
        </form>

        <div class="links">
            <a href="{{ route('admin.students.create') }}" class="secondary">Student Registration</a>
            <a href="{{ route('admin.dashboard') }}" class="secondary">← Back</a>
        </div>

        @if(session('notfound'))
            <p class="msg error">{{ session('notfound') }}</p>
        @endif
        @if(session('success'))
            <p class="msg">{{ session('success') }}</p>
        @endif
    </div>
</body>
</html>