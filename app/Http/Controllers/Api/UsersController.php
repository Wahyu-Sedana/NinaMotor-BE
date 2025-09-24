<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'           => 'required|string|max:255',
            'email'          => 'required|string|email|unique:tb_users,email',
            'password'       => 'required|string|min:6|confirmed',
            'role'           => 'nullable|string',
            'alamat'         => 'nullable|string',
            'no_telp'  => 'nullable|string',
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
            $user = User::create([
                'nama'           => $request->nama,
                'email'          => $request->email,
                'password'       => Hash::make($request->password),
                'role'           => $request->role ?? 'customer',
                'alamat'         => $request->alamat,
                'no_telp'  => $request->image_profile,
                'fcm_token'      => $request->fcm_token,
            ]);

            Log::debug($user);

            return response()->json([
                'status'  => 200,
                'message' => 'Register success',
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

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'password'  => 'required|string',
            'fcm_token' => 'nullable|string',
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
                    'user'    => []
                ], 200);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Invalid credentials',
                    'user'    => []
                ], 200);
            }

            if ($request->has('fcm_token') && $request->fcm_token) {
                $user->fcm_token = $request->fcm_token;
                $user->save();

                Log::info('FCM token updated for user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'fcm_token_updated' => true
                ]);
            }

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

            $user->fcm_token = null;
            $user->save();

            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out and FCM token cleared', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Logout success',
            ]);
        } catch (\Throwable $th) {
            Log::error('Logout error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Logout failed',
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
            'alamat'         => 'nullable|string',
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
            if ($request->has('alamat')) {
                $user->alamat = $request->alamat;
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
                    'alamat',
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

    public function checkUserByEmail(Request $request)
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

            if ($user) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'User found',
                    'user'    => $user,
                ]);
            } else {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User not found',
                    'user'    => null,
                ], 200);
            }
        } catch (\Throwable $th) {
            Log::error('Check user by email error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to check user',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
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

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Current password is incorrect',
                ], 200);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('User password updated', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Password updated successfully',
            ]);
        } catch (\Throwable $th) {
            Log::error('Reset password error: ' . $th->getMessage());

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to update password',
            ], 500);
        }
    }
}
