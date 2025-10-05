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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
            margin-top: 0;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }

        .info-box {
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>✉️ Verifikasi Email</h1>
        </div>

        <div class="content">
            <h2>Halo, {{ $userName }}!</h2>

            <p>Terima kasih telah mendaftar! Untuk melanjutkan, silakan verifikasi email Anda dengan mengklik tombol di
                bawah ini:</p>

            <center>
                <a href="{{ $verificationUrl }}" class="button">Verifikasi Email Saya</a>
            </center>

            <div class="info-box">
                <p style="margin: 0;"><strong>💡 Tips:</strong> Link verifikasi ini hanya berlaku untuk satu kali
                    penggunaan.</p>
            </div>

            <p>Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
            <p style="word-break: break-all; color: #667eea;">{{ $verificationUrl }}</p>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Jika Anda tidak membuat akun ini, abaikan email ini.
            </p>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
