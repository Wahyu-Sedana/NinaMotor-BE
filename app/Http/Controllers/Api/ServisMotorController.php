<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\Controller;
use App\Models\ServisMotor;
use App\Models\User;
use App\Notifications\NewServisNotification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServisMotorController extends Controller
{
    private $adminNotification;

    public function __construct()
    {
        $this->adminNotification = new FirebaseService();
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $servisList = ServisMotor::where('user_id', $user->id)->latest()->get();

        return response()->json([
            'status' => 200,
            'data' => $servisList
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_kendaraan' => 'required|string|max:20',
            'jenis_motor' => 'required|in:matic,manual',
            'keluhan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 200);
        }

        $servis = ServisMotor::create([
            'user_id' => $request->user()->id,
            'no_kendaraan' => $request->no_kendaraan,
            'jenis_motor' => $request->jenis_motor,
            'keluhan' => $request->keluhan,
            'status' => 'pending',
        ]);

        $admin = User::where('role', 'admin')->first();

        if ($admin) {
            $admin->notify(new NewServisNotification($servis));
        }

        $this->adminNotification->sendNotificationToAdmin(
            'Pengajuan Servis Baru',
            'Motor ' . $servis->no_kendaraan . ' dengan keluhan: ' . $servis->keluhan,
            ['servis_id' => $servis->id]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Pengajuan servis berhasil dikirim',
            'data' => $servis
        ]);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $servis = ServisMotor::where('id', $id)->where('user_id', $user->id)->first();

        if (!$servis) {
            return response()->json([
                'status' => 404,
                'message' => 'Data tidak ditemukan'
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'data' => $servis
        ]);
    }
}
