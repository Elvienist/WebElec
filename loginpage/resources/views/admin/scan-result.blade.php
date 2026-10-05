<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Student Information</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
    <div class="card text-center">

        <div class="name-box">
            <h2>{{ $student->first_name }} {{ $student->last_name }}</h2>
        </div>

        @if($student->picture)
            <img src="{{ asset('storage/' . $student->picture) }}" alt="Student Picture">
        @endif

        <div class="field">{{ $student->student_number }}</div>
        <div class="field">{{ $student->year_section }}</div>
        <div class="field">{{ $student->course }}</div>

        <a href="{{ route('admin.dashboard') }}" class="back">← Back</a>
    </div>

    <script>
        // Pagkalipas ng 5 segundo (5000 milliseconds), babalik sa dashboard
        setTimeout(function () {
            window.location.href = "{{ route('admin.scan') }}";
        }, 5000);
    </script>
</body>
</html>