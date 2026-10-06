<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
<div class="card">
    <h2>Create Account</h2>
    <p class="subtitle">Sign up to continue</p>

    <form action="{{ route('signup.store') }}" method="POST">
        @csrf

        <div class="input-group">
            <label>I am a</label>
            <select name="role" id="role">
                <option value="student" @selected(old('role', 'student') === 'student')>Student</option>
                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
            </select>
            @error('role')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group" id="student-field">
            <label>Student Number</label>
            <input type="text" name="student_number" value="{{ old('student_number') }}" placeholder="e.g. 2024-0001">
            @error('student_number')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group" id="admin-field" style="display:none;">
            <label>Admin Code</label>
            <input type="password" name="admin_code" placeholder="Enter admin code">
            @error('admin_code')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" id="password" placeholder="At least 8 characters" required>
            @include('auth.partials.strength')
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" placeholder="Repeat your password" required>
        </div>

        <button type="submit" class="btn">Sign up</button>
        <a href="{{ route('login') }}" class="back">← Back to login</a>
    </form>
</div>

<script>
    const role = document.getElementById('role');
    function toggleRole() {
        const isAdmin = role.value === 'admin';
        document.getElementById('admin-field').style.display = isAdmin ? 'block' : 'none';
        document.getElementById('student-field').style.display = isAdmin ? 'none' : 'block';
    }
    role.addEventListener('change', toggleRole);
    toggleRole();
</script>
@include('auth.partials.toggle-password')
</body>
</html>