<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|unique:users',
            'password'       => 'required|string|min:6|confirmed',
            'role'           => 'nullable|string',
            'alamat'         => 'nullable|string',
            'no_kendaraan'   => 'nullable|string',
            'nama_kendaraan' => 'nullable|string',
            'image_profile'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?? 'customer',
            'alamat'         => $request->alamat,
            'no_kendaraan'   => $request->no_kendaraan,
            'nama_kendaraan' => $request->nama_kendaraan,
            'image_profile'  => $request->image_profile,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Register success',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Login success',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout success',
        ]);
    }
}
