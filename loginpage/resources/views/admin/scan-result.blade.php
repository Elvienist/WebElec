<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Information</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; display:flex; justify-content:center; align-items:center; min-height:100vh; position:relative; overflow:hidden; }
    body::before { content:""; position:absolute; top:-150px; left:-100px; width:400px; height:400px; background:radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%); }
    body::after { content:""; position:absolute; bottom:-150px; right:-100px; width:450px; height:450px; background:radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%); }
    .card { background-color:rgba(15,16,22,0.85); padding:35px; border-radius:22px; width:360px; border:1px solid rgba(70,130,255,0.25); box-shadow:0 0 40px rgba(30,100,255,0.15); position:relative; z-index:1; text-align:center; color:#fff; }

    /* Bagong box para sa pangalan */
    .name-box {
        border: 1px solid rgba(70, 130, 255, 0.4);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 15px;
        background-color: rgba(46, 125, 255, 0.08);
    }
    .name-box h2 {
        font-size: 20px;
        color: #fff;
    }

    .card img { width:150px; height:150px; object-fit:cover; border-radius:12px; margin:15px 0; border:1px solid #2a2f40; }
    .field { background:#0c0d13; border:1px solid #262b3a; border-radius:10px; padding:10px; margin-bottom:10px; font-size:14px; }
    .back { display:inline-block; margin-top:15px; color:#4a9bff; text-decoration:none; font-size:13px; }
</style>
</head>
<body>
    <div class="card">

        <div class="name-box">
            <h2>{{ $student->first_name }} {{ $student->last_name }}</h2>
        </div>

        @if($student->picture)
            <img src="{{ asset('storage/' . $student->picture) }}" alt="Student Picture">
        @endif

        <div class="field">{{ $student->student_number }}</div>
        <div class="field">{{ $student->year_section }}</div>
        <div class="field">{{ $student->course }}</div>

        <a href="{{ route('dashboard') }}" class="back">← Back</a>
    </div>

    <script>
        // Pagkalipas ng 5 segundo (5000 milliseconds), babalik sa dashboard
        setTimeout(function () {
            window.location.href = "{{ route('admin.scan') }}";
        }, 5000);
    </script>
</body>
</html>