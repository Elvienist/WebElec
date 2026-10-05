<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student List</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
    body { background-color:#030308; padding:40px; color:#fff; }
    h2 { margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; background:rgba(15,16,22,0.85); border-radius:12px; overflow:hidden; }
    th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #262b3a; font-size:14px; }
    th { background:#1c1f2b; }
    .back, .add { display:inline-block; margin-top:15px; margin-right:15px; color:#4a9bff; text-decoration:none; font-size:13px; }
</style>
</head>
<body>
    <h2>Registered Students</h2>
    <table>
        <tr>
            <th>Student Number</th>
            <th>Name</th>
            <th>Year & Section</th>
            <th>Course</th>
        </tr>
        @forelse($students as $student)
        <tr>
            <td>{{ $student->student_number }}</td>
            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
            <td>{{ $student->year_section }}</td>
            <td>{{ $student->course }}</td>
        </tr>
        @empty
        <tr><td colspan="4">No students registered yet.</td></tr>
        @endforelse
    </table>
    <a href="{{ route('admin.students.create') }}" class="add">+ Register Student</a>
    <a href="{{ route('admin.dashboard') }}" class="back">← Back to Dashboard</a>
</body>
</html>