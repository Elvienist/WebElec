<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* BACKGROUD */
        body {
            background-color: #030308;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Glow part 1 */
        body::before {
            content: "";
            position: absolute;
            top: -150px;
            left: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(40,110,255,0.35) 0%, transparent 70%);
            z-index: 0;
        }

        /* Glow #2 */
        body::after {
            content: "";
            position: absolute;
            bottom: -150px;
            right: -100px;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(30,140,255,0.45) 0%, transparent 70%);
            z-index: 0;
        }

        /* LOGIN CARD */
        .login-box {
            background-color: rgba(15, 16, 22, 0.85);
            padding: 45px 35px;
            border-radius: 22px;
            width: 350px;
            border: 1px solid rgba(70, 130, 255, 0.25);
            box-shadow: 0 0 40px rgba(30, 100, 255, 0.15);
            position: relative;
            z-index: 1;
        }

        /* Glow for logo natin/badge */
        .badge {
    width: 80px;
    height: 80px;
    margin: 0 auto 10px auto;
    display: block;
    object-fit: contain;
    filter: drop-shadow(0 0 12px rgba(46, 125, 255, 0.6));
}
        .login-box h2 {
            color: #ffffff;
            text-align: center;
            font-size: 26px;
            margin-bottom: 5px;
        }

        .login-box p.subtitle {
            color: #7c8aa8;
            text-align: center;
            font-size: 13px;
            margin-bottom: 28px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #262b3a;
            border-radius: 12px;
            background-color: #0c0d13;
            color: #ffffff;
            outline: none;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        /* for highlight to */
        .input-group input:focus {
            border-color: #2e7dff;
            box-shadow: 0 0 12px rgba(46, 125, 255, 0.5);
        }

        .forgot-row {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-row a {
            color: #6f7d9c;
            font-size: 12px;
            text-decoration: none;
        }

        /* for gradient button  */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2e7dff, #1450c9);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(46, 125, 255, 0.45);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            box-shadow: 0 0 30px rgba(46, 125, 255, 0.7);
            transform: translateY(-2px);
        }

        .extra-text {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: #7c8aa8;
        }

        .extra-text a {
            color: #4a9bff;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="login-box">
       <img src="{{ asset('images/orsus-removebg-preview.png') }}" class="badge" alt="Logo">
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to continue</p>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="input-group">
                <input type="email" name="email" placeholder="Enter your email address" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="forgot-row">
                <a href="#">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login">Log in</button>

            <div class="extra-text">
                Don't have an account? <a href="#">Sign up</a>