<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student List</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="page">
    <h2>Registered Students</h2>
    <table>
        <tr>
            <th>Student Number</th>
            <th>Name</th>
            <th>Year & Section</th>
            <th>Course</th>
        </tr>
        @forelse($students as $student)
        <tr>
            <td>{{ $student->student_number }}</td>
            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
            <td>{{ $student->year_section }}</td>
            <td>{{ $student->course }}</td>
        </tr>
        @empty
        <tr><td colspan="4">No students registered yet.</td></tr>
        @endforelse
    </table>
    <a href="{{ route('admin.students.create') }}" class="add">+ Register Student</a>
    <a href="{{ route('admin.dashboard') }}" class="back">← Back to Dashboard</a>
</body>
</html>