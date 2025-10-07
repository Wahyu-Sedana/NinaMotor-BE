<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .email-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .email-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .email-body {
            padding: 40px 30px;
            color: #333;
        }

        .email-body h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .email-body p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }

        .verify-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }

        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }

        .alternative-link {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            word-break: break-all;
        }

        .alternative-link p {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .alternative-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .email-footer p {
            margin-bottom: 10px;
        }

        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 30px 0;
        }

        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .verify-button {
                padding: 14px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔐 Nina Motor</h1>
            <p>Verifikasi Email Anda</p>
        </div>

        <div class="email-body">
            <h2>Halo, {{ $user->nama }}!</h2>

            <p>
                Terima kasih telah mendaftar di <strong>Nina Motor</strong>.
                Untuk melengkapi pendaftaran Anda, silakan verifikasi alamat email Anda dengan mengklik tombol di bawah
                ini:
            </p>

            <div style="text-align: center;">
                <a href="{{ url('/api/verify-email/' . $token) }}" class="verify-button">
                    Verifikasi Email Saya
                </a>
            </div>

            <p style="margin-top: 30px;">
                Link verifikasi ini akan <strong>kedaluwarsa dalam 24 jam</strong>.
                Jika Anda tidak mendaftar akun ini, abaikan email ini.
            </p>

            <div class="divider"></div>

            <div class="alternative-link">
                <p><strong>Tidak bisa klik tombol?</strong></p>
                <p>Salin dan tempel link berikut ke browser Anda:</p>
                <a href="{{ url('/api/verify-email/' . $token) }}">
                    {{ url('/api/verify-email/' . $token) }}
                </a>
            </div>
        </div>

        <div class="email-footer">
            <p><strong>Nina Motor</strong></p>
            <p>Bengkel Motor Terpercaya</p>
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
            </p>
        </div>
    </div>
</body>

</html>
