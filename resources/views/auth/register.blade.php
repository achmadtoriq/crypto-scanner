<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Crypto Scanner Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 20%, rgba(16, 37, 66, 0.9) 0%, rgba(9, 19, 34, 1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            color: #fff;
            overflow-x: hidden;
        }

        .auth-container {
            width: 100%;
            max-width: 460px;
            padding: 20px;
            box-sizing: border-box;
            z-index: 2;
        }

        .auth-card {
            background: rgba(23, 27, 36, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .auth-logo {
            font-size: 38px;
            margin-bottom: 8px;
            display: inline-block;
            filter: drop-shadow(0 0 12px rgba(0, 212, 160, 0.4));
        }

        .auth-title {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 6px 0;
            background: linear-gradient(135deg, #fff 0%, #a0aec0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-subtitle {
            font-size: 13px;
            color: #8892a4;
            margin: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #a0aec0;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            background: rgba(13, 17, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #00d4a0;
            box-shadow: 0 0 0 3px rgba(0, 212, 160, 0.2);
            background: rgba(13, 17, 23, 0.95);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00d4a0 0%, #00b386 100%);
            border: none;
            border-radius: 10px;
            color: #091322;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(0, 212, 160, 0.35);
            margin-top: 10px;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(0, 212, 160, 0.5);
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #8892a4;
        }

        .auth-footer a {
            color: #00d4a0;
            text-decoration: none;
            font-weight: 700;
        }

        .alert-error {
            background: rgba(255, 77, 109, 0.15);
            border: 1px solid rgba(255, 77, 109, 0.4);
            color: #ff4d6d;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">⚡</div>
            <h1 class="auth-title">Daftar Akun Barumu</h1>
            <p class="auth-subtitle">Akses sinyal trading & fitur analisis otomatis</p>
        </div>

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required autofocus placeholder="John Trader">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="nama@email.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required placeholder="Ulangi password">
            </div>

            <button type="submit" class="btn-submit">
                ✨ BUAT AKUN SEKARANG
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="{{ route('login') }}">Login Sekarang</a>
        </div>
    </div>
</div>

</body>
</html>
