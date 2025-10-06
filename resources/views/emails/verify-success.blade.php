<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Verifikasi Berhasil' }}</title>
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: #f5f7fb;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 720px;
            margin: 80px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(15, 23, 42, .08);
            padding: 40px;
            text-align: center;
        }

        .icon {
            width: 72px;
            height: 72px;
            border-radius: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #16a34a;
            color: white;
            font-size: 36px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 22px;
        }

        p {
            margin: 0 0 18px;
            color: #475569;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>{{ $title ?? 'Verifikasi Berhasil' }}</h1>
        <p>{{ $message ?? 'Email Anda telah berhasil diverifikasi.' }}</p>
    </div>
</body>

</html>
