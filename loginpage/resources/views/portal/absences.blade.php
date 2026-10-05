<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Absences</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="page">
    <h2>My Absences</h2>
    <table>
        <tr><th>Date</th><th>Status</th></tr>
        @forelse($records as $record)
        <tr>
            <td>{{ $record->attendance_date }}</td>
            <td class="status">Absent</td>
        </tr>
        @empty
        <tr><td colspan="2">No absences recorded. 🎉</td></tr>
        @endforelse
    </table>
    <a href="{{ route('portal.dashboard') }}" class="back">← Back</a>
</body>
</html>