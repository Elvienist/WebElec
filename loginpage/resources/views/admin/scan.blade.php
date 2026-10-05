<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Attendance</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; display:flex; justify-content:center; align-items:center; height:100vh; position:relative; overflow:hidden; }
    body::before { content:""; position:absolute; top:-150px; left:-100px; width:400px; height:400px; background:radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%); }
    body::after { content:""; position:absolute; bottom:-150px; right:-100px; width:450px; height:450px; background:radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%); }
    .card { background-color:rgba(15,16,22,0.85); padding:40px 35px; border-radius:22px; width:380px; border:1px solid rgba(70,130,255,0.25); box-shadow:0 0 40px rgba(30,100,255,0.15); position:relative; z-index:1; text-align:center; }
    .card h2 { color:#fff; margin-bottom:5px; }
    .card p.subtitle { color:#7c8aa8; font-size:13px; margin-bottom:20px; }
    input[type=text] { width:100%; padding:14px 16px; border:1px solid #262b3a; border-radius:12px; background-color:#0c0d13; color:#fff; outline:none; font-size:14px; text-align:center; }
    input[type=text]:focus { border-color:#2e7dff; box-shadow:0 0 12px rgba(46,125,255,0.5); }
    .links { margin-top:18px; display:flex; gap:10px; justify-content:center; }
    .links a { padding:10px 16px; border-radius:10px; font-size:13px; text-decoration:none; }
    .links a.secondary { background:#1c1f2b; color:#ccc; border:1px solid #2a2f40; }
    .msg { margin-top:15px; font-size:13px; color:#4a9bff; }
    .msg.error { color:#ff6b6b; }
</style>
</head>
<body>
    <div class="card">
        <h2>Student Attendance</h2>
        <p class="subtitle">Enter the student number and press Enter.</p>

        <form action="{{ route('admin.scan.search') }}" method="POST">
            @csrf
            <input type="text" name="student_number" placeholder="Enter Student Number" autofocus>
        </form>

        <div class="links">
            <a href="{{ route('admin.students.create') }}" class="secondary">Student Registration</a>
            <a href="{{ route('logout') }}" class="secondary">Logout</a>
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