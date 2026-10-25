<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Records</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="page">
    <h2>Attendance Records — {{ $date }}</h2>

    <form class="filter" method="GET" action="{{ route('admin.attendance') }}">
        <input type="date" name="date" value="{{ $date }}">
        <button type="submit">View</button>
    </form>

    @if(session('success'))
        <p class="msg">{{ session('success') }}</p>
    @endif
    @if(session('notfound'))
        <p class="msg">{{ session('notfound') }}</p>
    @endif

    <table>
        <tr>
            <th>Student Number</th>
            <th>Name</th>
            <th>Time</th>
            <th>Status</th>
        </tr>
        @forelse($records as $record)
        <tr>
            <td>{{ $record->student->student_number }}</td>
            <td>{{ $record->student->first_name }} {{ $record->student->last_name }}</td>
            <td>{{ $record->attendance_time ?? '—' }}</td>
            <td class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</td>
        </tr>
        @empty
        <tr><td colspan="4">No records for this date.</td></tr>
        @endforelse
    </table>

    <div class="mark-absent">
        <h3 style="margin-bottom:10px;">Mark a Student Absent</h3>
        <form method="POST" action="{{ route('admin.attendance.markAbsent') }}">
            @csrf
            <input type="text" name="student_number" placeholder="Student Number">
            <input type="date" name="attendance_date" value="{{ $date }}">
            <button type="submit">Mark Absent</button>
        </form>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="back">← Back to Dashboard</a>
</body>
</html>