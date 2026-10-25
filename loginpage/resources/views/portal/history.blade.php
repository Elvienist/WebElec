<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance History</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="page">
    <h2>My Attendance History</h2>
    <table>
        <tr><th>Date</th><th>Time</th><th>Status</th></tr>
        @forelse($records as $record)
        <tr>
            <td>{{ $record->attendance_date }}</td>
            <td>{{ $record->attendance_time ?? '—' }}</td>
            <td class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No records yet.</td></tr>
        @endforelse
    </table>
    <a href="{{ route('portal.dashboard') }}" class="back">← Back</a>
</body>
</html>