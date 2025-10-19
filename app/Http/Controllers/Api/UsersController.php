<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'status'  => 200,
                'message' => 'Profile retrieved successfully',
                'user'    => $user,
            ]);
        } catch (\Throwable $th) {
            Log::error('Profile error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to retrieve profile',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->phone_id = null;
                $user->fcm_token = null;
                $user->save();

                $request->user()->currentAccessToken()->delete();

                Log::info('User logged out successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Logout success',
            ]);
        } catch (\Throwable $th) {
            Log::error('Logout error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Save or update FCM token for authenticated user
     */
    public function saveFCMToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = $request->user();
            $oldToken = $user->fcm_token;

            $user->fcm_token = $request->fcm_token;
            $user->save();

            Log::info('FCM token saved/updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_token' => $oldToken ? 'exists' : 'none',
                'new_token' => 'updated'
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'FCM token saved successfully',
            ]);
        } catch (\Throwable $th) {
            Log::error('Save FCM token error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to save FCM token',
            ], 500);
        }
    }

    /**
     * Update user profile including FCM token
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'           => 'nullable|string|max:255',
            'no_telp'        => 'nullable|string|max:13',
            'profile'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = $request->user();

            if ($request->has('nama')) {
                $user->nama = $request->nama;
            }
            if ($request->has('no_telp')) {
                $user->no_telp = $request->no_telp;
            }
            if ($request->hasFile('profile')) {
                $path = $request->file('profile')->store('profiles', 'public');
                $user->profile = $path;
            }

            $user->save();

            Log::info('User profile updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated_fields' => array_keys($request->only([
                    'nama',
                    'no_telp',
                    'profile',
                ]))
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Profile updated successfully',
                'user'    => $user,
            ]);
        } catch (\Throwable $th) {
            Log::error('Update profile error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to update profile',
            ], 500);
        }
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'           => 'required|string|max:255',
            'email'          => 'required|string|email|unique:tb_users,email',
            'password'       => 'required|string|min:6|confirmed',
            'no_telp'       => 'required|string|max:13',
            'role'           => 'nullable|string',
            'fcm_token'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            // Generate verification token
            $verificationToken = Str::random(60);

            $user = User::create([
                'nama'                      => $request->nama,
                'email'                     => $request->email,
                'password'                  => Hash::make($request->password),
                'role'                      => $request->role ?? 'customer',
                'no_telp'                   => $request->no_telp,
                'fcm_token'                 => $request->fcm_token,
                'email_verification_token'  => $verificationToken,
                'email_verified_at'         => null,
            ]);

            try {
                Mail::to($user->email)->send(new EmailVerification($user, $verificationToken));

                Log::info('Verification email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send verification email: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Register success. Please check your email to verify your account.',
                'user'    => $user,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Register error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Register failed. Internal server error.',
            ], 500);
        }
    }

    public function verifyEmail(Request $request, $token)
    {
        try {
            $user = User::where('email_verification_token', $token)->first();

            if (!$user) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 404,
                        'message' => 'Invalid verification token',
                    ], 404);
                }

                return response()
                    ->view('auth.verify-failed', [
                        'title' => 'Invalid Token',
                        'message' => 'Token verifikasi tidak valid atau sudah kadaluarsa.',
                    ], 404);
            }

            if ($user->email_verified_at) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 200,
                        'message' => 'Email already verified',
                    ], 200);
                }

                return view('auth.verify-success', [
                    'title' => 'Email Sudah Diverifikasi',
                    'message' => 'Email Anda sudah terverifikasi. Silakan login.',
                    'user' => $user,
                ]);
            }

            $user->email_verified_at = now();
            $user->email_verification_token = null;
            $user->save();

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Email verified successfully',
                ], 200);
            }

            return view('auth.verify-success', [
                'title' => 'Verifikasi Berhasil',
                'message' => 'Terima kasih — email Anda berhasil diverifikasi. Anda sekarang dapat masuk ke akun.',
                'user' => $user,
            ]);
        } catch (\Throwable $th) {
            Log::error('Email verification error: ' . $th->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'Email verification failed',
                ], 500);
            }

            return response()->view('auth.verify-failed', [
                'title' => 'Terjadi Kesalahan',
                'message' => 'Terjadi kesalahan saat memverifikasi email. Silakan coba lagi nanti.',
            ], 500);
        }
    }

    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User not found',
                ], 200);
            }

            if ($user->email_verified_at) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Email already verified',
                ], 200);
            }

            // Generate new token
            $verificationToken = Str::random(60);
            $user->email_verification_token = $verificationToken;
            $user->save();

            // Send verification email
            Mail::to($user->email)->send(new EmailVerification($user, $verificationToken));

            Log::info('Verification email resent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Verification email sent successfully',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Resend verification error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to send verification email',
            ], 500);
        }
    }

    public function showResetForm(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return view('auth.reset-form')->with('error', 'Token reset tidak ditemukan.');
        }

        $user = User::where('password_reset_token', $token)->first();

        if (!$user) {
            return view('auth.reset-form')->with('error', 'Token tidak valid.');
        }

        if (Carbon::now()->greaterThan($user->password_reset_expires)) {
            return view('auth.reset-form')->with('error', 'Token telah kedaluwarsa.');
        }

        return view('auth.reset-form', ['token' => $token]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User not found',
                ], 200);
            }

            // Generate reset token
            $resetToken = Str::random(60);

            $user->password_reset_token = $resetToken;
            $user->password_reset_expires = Carbon::now()->addHours(1);
            $user->save();

            // Send reset password email
            Mail::to($user->email)->send(new PasswordResetMail($user, $resetToken));

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Password reset link sent to your email',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Forgot password error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to send reset password email',
            ], 500);
        }
    }

    public function resetPasswordWithToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'        => 'required|string',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = User::where('password_reset_token', $request->token)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Invalid reset token',
                ], 200);
            }

            // Check if token expired
            if (Carbon::now()->greaterThan($user->password_reset_expires)) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Reset token has expired',
                ], 200);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->password_reset_token = null;
            $user->password_reset_expires = null;
            $user->save();

            Log::info('Password reset successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return view('auth.verify-success', [
                'title' => 'Reset Password Berhasil',
                'message' => 'Terima kasih — anda sudah berhasil reset password',
                'user' => $user,
            ]);
        } catch (\Throwable $th) {
            Log::error('Reset password error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to reset password',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'password'  => 'required|string',
            'fcm_token' => 'nullable|string',
            'phone_id'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 200);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User Tidak di temukan',
                    'user'    => []
                ], 200);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Email atau password salah.',
                    'user'    => []
                ], 200);
            }

            // Check email verification
            if (!$user->email_verified_at) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'Verifikasi email anda terlebih dahulu/',
                    'user'    => []
                ], 200);
            }

            if ($user->phone_id && $user->phone_id !== $request->phone_id) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'Akun ini sudah digunakan di perangkat lain.',
                    'user'    => []
                ], 200);
            }

            $user->phone_id = $request->phone_id;

            if ($request->has('fcm_token') && $request->fcm_token) {
                $user->fcm_token = $request->fcm_token;
            }

            $user->save();

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone_id' => $request->phone_id,
                'fcm_token_updated' => $request->has('fcm_token')
            ]);

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'status'  => 200,
                'message' => 'Login success',
                'user'    => $user,
                'token'   => $token,
            ]);
        } catch (\Throwable $th) {
            Log::error('Login error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
