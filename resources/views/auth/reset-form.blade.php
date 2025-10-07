<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
        <h2>Atur Ulang Password</h2>

        @if (session('status'))
            <p style="color: green;">{{ session('status') }}</p>
        @endif

        @if (session('error'))
            <p style="color: red;">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ url('/api/reset-password') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <p>
                <label for="password">Password Baru:</label><br>
                <input type="password" id="password" name="password" required
                    style="width: 100%; padding: 8px; margin-top: 5px;">
                @error('password')
                    <span style="color: red; font-size: 0.9em;">{{ $message }}</span>
                @enderror
            </p>

            <p>
                <label for="password_confirmation">Konfirmasi Password:</label><br>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    style="width: 100%; padding: 8px; margin-top: 5px;">
            </p>

            <button type="submit"
                style="padding: 10px 15px; background-color: #f00; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Reset Password
            </button>
        </form>
    </div>
</body>

</html>
