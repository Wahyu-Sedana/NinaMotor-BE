<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .content {
            padding: 40px 30px;
        }

        .content h2 {
            color: #f5576c;
            margin-top: 0;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin: 20px 0;
            transition: transform 0.2s;
        }

        .button:hover {
            transform: scale(1.05);
        }

        .token-box {
            background-color: #fff3cd;
            border: 2px dashed #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }

        .token-code {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #d9534f;
            letter-spacing: 2px;
        }

        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Reset Password</h1>
        </div>

        <div class="content">
            <h2>Halo, {{ $userName }}!</h2>

            <p>Kami menerima permintaan untuk mereset password akun Anda. Klik tombol di bawah ini untuk melanjutkan:
            </p>

            <center>
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </center>

            <div class="warning-box">
                <p style="margin: 0;"><strong>⚠️ Penting:</strong> Link reset password ini akan kadaluarsa dalam 1 jam.
                </p>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <strong>Jika Anda tidak meminta reset password, abaikan email ini.</strong> Password Anda tidak akan
                berubah.
            </p>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
