<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
    <div class="card text-center">
        <h2>Admin Dashboard</h2>
        <div class="menu">
            <a href="{{ route('admin.scan') }}">Scan / Record Attendance</a>
            <a href="{{ route('admin.students') }}">Manage Students</a>
            <a href="{{ route('admin.students.create') }}">Register New Student</a>
            <a href="{{ route('admin.attendance') }}">View Attendance Records</a>
        </div>
        <a href="{{ route('logout') }}" class="logout">Logout</a>
    </div>
</body>
</html>