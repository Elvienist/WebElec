<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Records</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; padding:40px; color:#fff; }
    h2 { margin-bottom:15px; }
    form.filter { margin-bottom:20px; }
    form.filter input[type=date] { padding:8px; border-radius:8px; border:1px solid #2a2f40; background:#0c0d13; color:#fff; }
    form.filter button { padding:8px 14px; border-radius:8px; border:none; background:#2e7dff; color:#fff; cursor:pointer; }
    table { width:100%; border-collapse:collapse; background:rgba(15,16,22,0.85); border-radius:12px; overflow:hidden; margin-bottom:25px; }
    th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #262b3a; font-size:14px; }
    th { background:#1c1f2b; }
    .status-present { color:#4ade80; }
    .status-absent { color:#ff6b6b; }
    .mark-absent { background:rgba(15,16,22,0.85); padding:20px; border-radius:12px; max-width:400px; }
    .mark-absent input { width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #2a2f40; background:#0c0d13; color:#fff; }
    .mark-absent button { padding:10px 14px; border-radius:8px; border:none; background:#d64545; color:#fff; cursor:pointer; }
    .back { display:inline-block; margin-top:15px; color:#4a9bff; text-decoration:none; font-size:13px; }
    .msg { margin-bottom:15px; font-size:13px; color:#4a9bff; }
</style>
</head>
<body>
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