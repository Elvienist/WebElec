<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; display:flex; justify-content:center; align-items:center; min-height:100vh; position:relative; overflow:hidden; }
    body::before { content:""; position:absolute; top:-150px; left:-100px; width:400px; height:400px; background:radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%); }
    body::after { content:""; position:absolute; bottom:-150px; right:-100px; width:450px; height:450px; background:radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%); }
    .card { background-color:rgba(15,16,22,0.85); padding:35px; border-radius:22px; width:380px; border:1px solid rgba(70,130,255,0.25); box-shadow:0 0 40px rgba(30,100,255,0.15); position:relative; z-index:1; color:#fff; }
    .card h2 { text-align:center; margin-bottom:5px; }
    .card p.sub { text-align:center; color:#7c8aa8; font-size:13px; margin-bottom:20px; }
    .field { background:#0c0d13; border:1px solid #262b3a; border-radius:10px; padding:10px; margin-bottom:8px; font-size:13px; display:flex; justify-content:space-between; }
    .status-present { color:#4ade80; }
    .status-absent { color:#ff6b6b; }
    .menu a { display:block; padding:10px; margin-top:8px; background:#1c1f2b; border:1px solid #2a2f40; border-radius:10px; color:#fff; text-decoration:none; font-size:13px; text-align:center; }
    .logout { display:block; margin-top:15px; text-align:center; color:#ff6b6b; text-decoration:none; font-size:12px; }
</style>
</head>
<body>
    <div class="card">
        <h2>{{ $student->first_name }} {{ $student->last_name }}</h2>
        <p class="sub">Recent Attendance</p>

        @forelse($records as $record)
        <div class="field">
            <span>{{ $record->attendance_date }}</span>
            <span class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</span>
        </div>
        @empty
        <div class="field">No attendance records yet.</div>
        @endforelse

        <div class="menu">
            <a href="{{ route('portal.history') }}">View Full History</a>
            <a href="{{ route('portal.absences') }}">View My Absences</a>
        </div>
        <a href="{{ route('logout') }}" class="logout">Logout</a>
    </div>
</body>
</html>