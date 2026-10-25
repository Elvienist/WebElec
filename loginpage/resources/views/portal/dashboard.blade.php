<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
    <div class="card">
        <h2>{{ $student->first_name }} {{ $student->last_name }}</h2>
        <p class="subtitle">Recent Attendance</p>

        @forelse($records as $record)
        <div class="field field-row">
            <span>{{ $record->attendance_date }}</span>
            <span class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</span>
        </div>
        @empty
        <div class="field field-row">No attendance records yet.</div>
        @endforelse

        <div class="menu">
            <a href="{{ route('portal.history') }}">View Full History</a>
            <a href="{{ route('portal.absences') }}">View My Absences</a>
        </div>
        <a href="{{ route('logout') }}" class="logout">Logout</a>
    </div>
</body>
</html>