<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Absences</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; padding:40px; color:#fff; }
    h2 { margin-bottom:15px; }
    table { width:100%; border-collapse:collapse; background:rgba(15,16,22,0.85); border-radius:12px; overflow:hidden; }
    th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #262b3a; font-size:14px; }
    th { background:#1c1f2b; }
    td.status { color:#ff6b6b; }
    .back { display:inline-block; margin-top:15px; color:#4a9bff; text-decoration:none; font-size:13px; }
</style>
</head>
<body>
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