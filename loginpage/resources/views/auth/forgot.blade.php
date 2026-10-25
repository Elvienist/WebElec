<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
<div class="card">
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your email and we'll send a reset link.</p>

    @if(session('success'))<p class="msg">{{ session('success') }}</p>@endif

    <form action="{{ route('password.email') }}" method="POST">
        @csrf
        <div class="input-group">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn">Send reset link</button>
        <a href="{{ route('login') }}" class="back">← Back to login</a>
    </form>
</div>
</body>
</html>