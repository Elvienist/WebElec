<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; display:flex; justify-content:center; align-items:center; min-height:100vh; position:relative; overflow:hidden; }
    body::before { content:""; position:absolute; top:-150px; left:-100px; width:400px; height:400px; background:radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%); }
    body::after { content:""; position:absolute; bottom:-150px; right:-100px; width:450px; height:450px; background:radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%); }
    .card { background-color:rgba(15,16,22,0.85); padding:40px 35px; border-radius:22px; width:380px; border:1px solid rgba(70,130,255,0.25); box-shadow:0 0 40px rgba(30,100,255,0.15); position:relative; z-index:1; text-align:center; color:#fff; }
    .card h2 { margin-bottom:25px; }
    .menu a { display:block; padding:14px; margin-bottom:10px; background:#1c1f2b; border:1px solid #2a2f40; border-radius:12px; color:#fff; text-decoration:none; font-size:14px; }
    .menu a:hover { background:#262b3a; }
    .logout { display:block; margin-top:15px; color:#ff6b6b; text-decoration:none; font-size:13px; }
</style>
</head>
<body>
    <div class="card">
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