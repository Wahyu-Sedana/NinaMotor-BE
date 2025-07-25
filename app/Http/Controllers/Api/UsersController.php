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
            'no_kendaraan'   => 'nullable|string',
            'nama_kendaraan' => 'nullable|string',
            'image_profile'  => 'nullable|string',
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
                'no_kendaraan'   => $request->no_kendaraan,
                'nama_kendaraan' => $request->nama_kendaraan,
                'image_profile'  => $request->image_profile,
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
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'User not found',
                    'user' => []
                ], 200);
            }

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Invalid credentials',
                    'user' => []
                ], 200);
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
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Logout success',
        ]);
    }
}
