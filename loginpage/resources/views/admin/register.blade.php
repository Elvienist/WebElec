<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Registration</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; display:flex; justify-content:center; align-items:center; min-height:100vh; position:relative; overflow:hidden; }
    body::before { content:""; position:absolute; top:-150px; left:-100px; width:400px; height:400px; background:radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%); }
    body::after { content:""; position:absolute; bottom:-150px; right:-100px; width:450px; height:450px; background:radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%); }
    .card { background-color:rgba(15,16,22,0.85); padding:35px; border-radius:22px; width:380px; border:1px solid rgba(70,130,255,0.25); box-shadow:0 0 40px rgba(30,100,255,0.15); position:relative; z-index:1; }
    .card h2 { color:#fff; text-align:center; margin-bottom:20px; }
    .input-group { margin-bottom:14px; }
    .input-group label { display:block; color:#7c8aa8; font-size:12px; margin-bottom:5px; }
    .input-group input { width:100%; padding:12px 14px; border:1px solid #262b3a; border-radius:10px; background-color:#0c0d13; color:#fff; outline:none; font-size:14px; }
    .input-group input:focus { border-color:#2e7dff; box-shadow:0 0 12px rgba(46,125,255,0.5); }
    .btn { width:100%; padding:13px; background:linear-gradient(135deg,#2e7dff,#1450c9); border:none; border-radius:12px; color:#fff; font-size:15px; font-weight:bold; cursor:pointer; margin-top:8px; }
    .error { color:#ff6b6b; font-size:12px; margin-top:4px; }
    .back { display:block; text-align:center; margin-top:15px; color:#4a9bff; text-decoration:none; font-size:13px; }
</style>
</head>
<body>
    <div class="card">
        <h2>Student Registration</h2>
        <form action="{{ route('admin.students.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="input-group">
                <label>Student Number</label>
                <input type="text" name="student_number" value="{{ old('student_number') }}">
                @error('student_number') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}">
                @error('first_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}">
                @error('last_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="input-group">
                <label>Year & Section</label>
                <input type="text" name="year_section" placeholder="e.g. BSIT 3-A" value="{{ old('year_section') }}">
                @error('year_section') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="input-group">
                <label>Course</label>
                <input type="text" name="course" value="{{ old('course') }}">
                @error('course') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="input-group">
                <label>Upload Picture</label>
                <input type="file" name="picture">
                @error('picture') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn">Submit</button>
        </form>
        <a href="{{ route('admin.scan') }}" class="back">← Back</a>
    </div>
</body>
</html>