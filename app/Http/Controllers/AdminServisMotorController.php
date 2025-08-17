<?php

namespace App\Http\Controllers;

use App\DataTables\AdminServisMotorDataTable;
use App\Models\ServisMotor;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Midtrans\Config;
use Illuminate\Support\Str;

class AdminServisMotorController extends Controller
{

    private $messaging;

    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/ninamotor-53934-firebase-adminsdk-fbsvc-1008728fde.json'));

            $this->messaging = $factory->createMessaging();
            Log::info('Firebase messaging object created successfully');
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    public function index(AdminServisMotorDataTable $dataTable)
    {
        return $dataTable->render('admin.servis.index', [
            'title' => 'Data Servis Motor',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * Parameter name harus sesuai dengan route: {servi}
     */
    public function edit($servi)
    {
        $servisMotor = ServisMotor::with(['user', 'transaksi'])->findOrFail($servi);
        return view('admin.servis.edit', compact('servisMotor'));
    }

    /**
     * Update the specified service in storage.
     * Parameter name harus sesuai dengan route: {servi}
     */
    public function update(Request $request, $servi)
    {
        $servisMotor = ServisMotor::findOrFail($servi);

        $validator = Validator::make($request->all(), [
            'no_kendaraan' => 'required|string|max:20',
            'jenis_motor' => 'required|string|max:100',
            'keluhan' => 'required|string',
            'status' => 'required|in:pending,rejected,in_service,done,priced',
            'catatan_admin' => 'nullable|string',
            'harga_servis' => 'nullable|numeric|min:0',
        ], [
            'no_kendaraan.required' => 'No kendaraan harus diisi',
            'no_kendaraan.max' => 'No kendaraan maksimal 20 karakter',
            'jenis_motor.required' => 'Jenis motor harus diisi',
            'jenis_motor.max' => 'Jenis motor maksimal 100 karakter',
            'keluhan.required' => 'Keluhan harus diisi',
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'harga_servis.numeric' => 'Harga servis harus berupa angka',
            'harga_servis.min' => 'Harga servis tidak boleh negatif',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $oldStatus = $servisMotor->status;
        $newStatus = $request->status;

        DB::beginTransaction();

        try {
            $servisMotor->update([
                'no_kendaraan' => $request->no_kendaraan,
                'jenis_motor' => $request->jenis_motor,
                'keluhan' => $request->keluhan,
                'status' => $newStatus,
                'catatan_admin' => $request->catatan_admin,
                'harga_servis' => $request->harga_servis,
            ]);

            if ($newStatus === 'priced' && $request->harga_servis && $request->harga_servis > 0) {
                $this->createTransaksiForServis($servisMotor, $request->harga_servis);
            }
            if ($oldStatus !== $newStatus) {
                $this->sendFirebaseNotification($servisMotor, $newStatus);
            }

            DB::commit();

            return redirect()->route('admin.servis.index')
                ->with('success', 'Data servis motor berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating servis motor: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal memperbarui data servis motor')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($servi)
    {
        $servisMotor = ServisMotor::with(['user', 'transaksi'])->findOrFail($servi);
        return view('admin.servis.show', compact('servisMotor'));
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy($servi)
    {
        DB::beginTransaction();

        try {
            $servisMotor = ServisMotor::findOrFail($servi);

            if ($servisMotor->transaksi) {
                $servisMotor->transaksi->update([
                    'status_pembayaran' => Transaksi::STATUS_CANCELLED
                ]);
            }

            $servisMotor->delete();

            DB::commit();

            return redirect()->route('admin.servis.index')
                ->with('success', 'Data servis motor berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->route('admin.servis.index')
                ->with('error', 'Gagal menghapus data servis motor');
        }
    }

    /**
     * Update status only
     */
    public function updateStatus(Request $request, $servi)
    {
        $servisMotor = ServisMotor::findOrFail($servi);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,rejected,in_service,done,priced',
            'catatan_admin' => 'nullable|string',
            'harga_servis' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid'
            ], 400);
        }

        $oldStatus = $servisMotor->status;
        $newStatus = $request->status;

        DB::beginTransaction();

        try {
            $servisMotor->update([
                'status' => $newStatus,
                'catatan_admin' => $request->catatan_admin ?? $servisMotor->catatan_admin,
                'harga_servis' => $request->harga_servis ?? $servisMotor->harga_servis,
            ]);

            if ($newStatus === 'priced' && $request->harga_servis && $request->harga_servis > 0 && !$servisMotor->hasTransaction()) {
                $this->createTransaksiForServis($servisMotor, $request->harga_servis);
            }

            if ($oldStatus !== $newStatus) {
                $this->sendFirebaseNotification($servisMotor, $newStatus);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status'
            ], 500);
        }
    }

    /**
     * Create transaction for service
     */
    private function createTransaksiForServis(ServisMotor $servisMotor, $harga)
    {
        try {
            $itemsData = [
                [
                    'type' => 'servis',
                    'id' => $servisMotor->id,
                    'nama' => "Servis Motor - {$servisMotor->jenis_motor}",
                    'no_kendaraan' => $servisMotor->no_kendaraan,
                    'keluhan' => $servisMotor->keluhan,
                    'harga' => $harga,
                    'qty' => 1,
                    'subtotal' => $harga
                ]
            ];

            $tempOrderId = 'ORD-' . time() . '-' . Str::random(6);

            $transaksi = Transaksi::create([
                'id' => $tempOrderId,
                'user_id' => $servisMotor->user_id,
                'total' => $harga,
                'status_pembayaran' => Transaksi::STATUS_PENDING,
                'tipe_transaksi' => Transaksi::TIPE_SERVIS,
                'items_data' => json_encode($itemsData),
                'metode_pembayaran' => 'cash',
            ]);

            $servisMotor->update([
                'transaksi_id' => $tempOrderId
            ]);

            Log::info('Transaksi servis berhasil dibuat', [
                'servis_id' => $servisMotor->id,
                'transaksi_id' => $tempOrderId,
                'total' => $harga
            ]);

            return $transaksi;
        } catch (\Exception $e) {
            Log::error('Error creating transaksi for servis: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send Firebase notification when status changes
     */
    private function sendFirebaseNotification(ServisMotor $servisMotor, string $newStatus)
    {
        try {
            if (!$this->messaging) {
                Log::warning('Firebase messaging not available, skipping notification');
                return false;
            }

            if (!$servisMotor->relationLoaded('user')) {
                $servisMotor->load('user');
            }

            if (!$servisMotor->user || !$servisMotor->user->fcm_token) {
                Log::info('User tidak memiliki FCM token, skip notification untuk servis ID: ' . $servisMotor->id);
                return false;
            }

            $notificationData = $this->getNotificationData($newStatus, $servisMotor);

            $message = CloudMessage::withTarget('token', $servisMotor->user->fcm_token)
                ->withNotification(Notification::create(
                    $notificationData['title'],
                    $notificationData['body']
                ))
                ->withData([
                    'type' => 'servis_status_update',
                    'servis_id' => (string)$servisMotor->id,
                    'status' => $newStatus,
                    'no_kendaraan' => $servisMotor->no_kendaraan,
                    'jenis_motor' => $servisMotor->jenis_motor,
                    'catatan_admin' => $servisMotor->catatan_admin ?? '',
                    'harga_servis' => $servisMotor->harga_servis ? (string)$servisMotor->harga_servis : '',
                    'transaksi_id' => $servisMotor->transaksi_id ?? '',
                    'updated_at' => $servisMotor->updated_at->toISOString(),
                ]);

            $this->messaging->send($message);

            Log::info('Firebase notification sent successfully', [
                'user_id' => $servisMotor->user->id,
                'servis_id' => $servisMotor->id,
                'status' => $newStatus,
                'fcm_token' => substr($servisMotor->user->fcm_token, 0, 20) . '...'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send Firebase notification', [
                'error' => $e->getMessage(),
                'servis_id' => $servisMotor->id,
                'status' => $newStatus,
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Get notification title and body based on status
     */
    private function getNotificationData(string $status, ServisMotor $servisMotor): array
    {
        $kendaraan = $servisMotor->no_kendaraan;
        $harga = $servisMotor->harga_servis ? number_format($servisMotor->harga_servis, 0, ',', '.') : '';

        switch ($status) {
            case 'pending':
                return [
                    'title' => 'Servis Motor - Menunggu Konfirmasi',
                    'body' => "Servis motor {$kendaraan} sedang menunggu konfirmasi dari teknisi kami."
                ];

            case 'rejected':
                return [
                    'title' => 'Servis Motor - Ditolak',
                    'body' => "Maaf, servis motor {$kendaraan} tidak dapat kami proses. Silakan hubungi kami untuk informasi lebih lanjut."
                ];

            case 'in_service':
                return [
                    'title' => 'Servis Motor - Sedang Dikerjakan',
                    'body' => "Motor {$kendaraan} sedang dalam proses servis. Kami akan segera menginformasikan jika sudah selesai."
                ];

            case 'priced':
                return [
                    'title' => 'Servis Motor - Estimasi Biaya',
                    'body' => $harga ? "Estimasi biaya servis motor {$kendaraan} adalah Rp {$harga}. Silakan lakukan pembayaran untuk melanjutkan." : "Estimasi biaya servis motor {$kendaraan} sudah tersedia. Silakan cek aplikasi untuk detailnya."
                ];

            case 'done':
                return [
                    'title' => 'Servis Motor - Selesai',
                    'body' => "Servis motor {$kendaraan} telah selesai! Motor Anda sudah siap diambil."
                ];

            default:
                return [
                    'title' => 'Update Status Servis Motor',
                    'body' => "Status servis motor {$kendaraan} telah diperbarui."
                ];
        }
    }
}
