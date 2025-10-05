<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
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

        $servis->load('user');

        AdminNotification::create([
            'type' => 'new_servis',
            'notifiable_type' => ServisMotor::class,
            'notifiable_id' => $servis->id,
            'title' => 'Pengajuan Servis Baru',
            'message' => "Motor {$servis->no_kendaraan} dengan keluhan: {$servis->keluhan}",
            'data' => [
                'servis_id' => $servis->id,
                'no_kendaraan' => $servis->no_kendaraan,
                'jenis_motor' => $servis->jenis_motor,
                'keluhan' => $servis->keluhan,
                'status' => 'pending',
                'user_name' => $servis->user->nama ?? 'Unknown',
                'user_id' => $servis->user_id,
            ],
            'action_url' => "/admin/servis/{$servis->id}",
            'is_read' => false,
        ]);


        $this->adminNotification->sendNotificationToAdmin(
            'Pengajuan Servis Baru',
            'Motor ' . $servis->no_kendaraan . ' dengan keluhan: ' . $servis->keluhan,
            ['servis_id' => $servis->id]
        );

        $this->sendAdminWebNotification($servis);

        return response()->json([
            'status' => 200,
            'message' => 'Pengajuan servis berhasil dikirim',
            'data' => $servis
        ]);
    }

    /**
     * Kirim notifikasi ke Admin Web via Firebase Topic
     */
    private function sendAdminWebNotification($servis)
    {
        try {
            if (!isset($this->messaging)) {
                $factory = (new \Kreait\Firebase\Factory)
                    ->withServiceAccount(storage_path('app/ninamotor-53934-firebase-adminsdk-fbsvc-1008728fde.json'));
                $this->messaging = $factory->createMessaging();
            }

            if (!$this->messaging) {
                return false;
            }

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('topic', 'admin_notifications')
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create(
                    '🔔 Pengajuan Servis Baru!',
                    "Motor {$servis->no_kendaraan} - {$servis->keluhan}"
                ))
                ->withData([
                    'type' => 'new_servis',
                    'servis_id' => (string)$servis->id,
                    'no_kendaraan' => $servis->no_kendaraan,
                    'jenis_motor' => $servis->jenis_motor,
                    'keluhan' => $servis->keluhan,
                    'status' => 'pending',
                    'user_name' => $servis->user->name ?? 'Unknown',
                    'created_at' => $servis->created_at->toISOString(),
                ]);

            $this->messaging->send($message);

            Log::info('Admin web notification sent via Firebase', [
                'servis_id' => $servis->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send admin web notification: ' . $e->getMessage());
            return false;
        }
    }
}
