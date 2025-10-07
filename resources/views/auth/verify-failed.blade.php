<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verifikasi Gagal' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: shake 0.5s ease-out;
        }

        .error-icon svg {
            width: 50px;
            height: 50px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }

        p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
            margin: 10px 5px;
            transition: transform 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 87, 108, 0.4);
        }

        .button-secondary {
            background: white;
            color: #f5576c;
            border: 2px solid #f5576c;
        }

        .button-secondary:hover {
            background: #fff5f7;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        @media only screen and (max-width: 600px) {
            .container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .button {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>

        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        <div>
            <a href="myapp://login" class="button">Buka Aplikasi</a>
            <a href="{{ url('/api/resend-verification') }}" class="button button-secondary">Kirim Ulang Email</a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: #999;">
            Jika masalah berlanjut, silakan hubungi customer service kami.
        </p>
    </div>
</body>

</html>
