<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="center">
<div class="card">
    <h2>Reset Password</h2>
    <p class="subtitle">Choose a new password.</p>

    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        @error('email')<p class="error">{{ $message }}</p>@enderror

        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="password" id="password" required>
            @include('auth.partials.strength')
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn">Update password</button>
    </form>
</div>
@include('auth.partials.toggle-password')
</body>
</html>