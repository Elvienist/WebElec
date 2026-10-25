<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Registration</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
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