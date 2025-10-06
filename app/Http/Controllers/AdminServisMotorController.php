<?php

namespace App\Http\Controllers;

use App\DataTables\AdminServisMotorDataTable;
use App\Exports\ServisMotorExport;
use App\Models\AdminNotification;
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
use Maatwebsite\Excel\Facades\Excel;

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
     */
    public function edit($servi)
    {
        $servisMotor = ServisMotor::with(['user', 'transaksi'])->findOrFail($servi);
        return view('admin.servis.edit', compact('servisMotor'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, $servi)
    {
        $servisMotor = ServisMotor::findOrFail($servi);

        $validator = Validator::make($request->all(), [
            'no_kendaraan' => 'required|string|max:20',
            'jenis_motor' => 'required|string|max:100',
            'keluhan' => 'required|string',
            'status' => 'required|in:pending,rejected,in_service,done,priced',
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
        return DB::transaction(function () use ($servisMotor, $harga) {
            try {
                if ($servisMotor->transaksi_id) {
                    $existingTransaksi = Transaksi::find($servisMotor->transaksi_id);
                    if ($existingTransaksi) {
                        Log::info('Transaksi untuk servis sudah ada', [
                            'servis_id' => $servisMotor->id,
                            'existing_transaksi_id' => $servisMotor->transaksi_id
                        ]);
                        return $existingTransaksi;
                    }
                }

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

                $maxAttempts = 10;
                $attempt = 0;

                do {
                    $tempOrderId = 'ORD-' . time() . '-' . Str::random(6);
                    $exists = Transaksi::where('id', $tempOrderId)->exists();
                    $attempt++;

                    if ($attempt >= $maxAttempts) {
                        throw new \Exception('Gagal generate unique order ID setelah ' . $maxAttempts . ' percobaan');
                    }
                } while ($exists);

                $transaksi = Transaksi::create([
                    'id' => $tempOrderId,
                    'user_id' => $servisMotor->user_id,
                    'total' => $harga,
                    'status_pembayaran' => Transaksi::STATUS_PENDING,
                    'items_data' => json_encode($itemsData),
                    'metode_pembayaran' => 'cash',
                    'type_pembelian' => 1
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
        });
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

        $notification = [
            'sound' => 'sound.aiff',
        ];

        switch ($status) {
            case 'pending':
                $notification += [
                    'title' => 'Servis Motor - Menunggu Konfirmasi',
                    'body' => "Servis motor {$kendaraan} sedang menunggu konfirmasi dari teknisi kami."
                ];
                break;

            case 'rejected':
                $notification += [
                    'title' => 'Servis Motor - Ditolak',
                    'body' => "Maaf, servis motor {$kendaraan} tidak dapat kami proses. Silakan hubungi kami untuk informasi lebih lanjut."
                ];
                break;

            case 'in_service':
                $notification += [
                    'title' => 'Servis Motor - Sedang Dikerjakan',
                    'body' => "Motor {$kendaraan} sedang dalam proses servis. Kami akan segera menginformasikan jika sudah selesai."
                ];
                break;

            case 'priced':
                $notification += [
                    'title' => 'Servis Motor - Estimasi Biaya',
                    'body' => $harga
                        ? "Estimasi biaya servis motor {$kendaraan} adalah Rp {$harga}. Silakan lakukan pembayaran untuk melanjutkan."
                        : "Estimasi biaya servis motor {$kendaraan} sudah tersedia. Silakan cek aplikasi untuk detailnya."
                ];
                break;

            case 'done':
                $notification += [
                    'title' => 'Servis Motor - Selesai',
                    'body' => "Servis motor {$kendaraan} telah selesai! Motor Anda sudah siap diambil."
                ];
                break;

            default:
                $notification += [
                    'title' => 'Update Status Servis Motor',
                    'body' => "Status servis motor {$kendaraan} telah diperbarui."
                ];
                break;
        }

        return $notification;
    }


    public function exportExcel(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);
        $bulan = $request->get('bulan');
        $search = $request->get('search');
        $tanggal = now()->format('Y-m-d_H-i-s');

        $filename = "Laporan_Servis_Motor_{$tanggal}.xlsx";
        return Excel::download(new ServisMotorExport($tahun,  $bulan, $search), $filename);
    }

    /**
     * Send notification to all admin when new servis created
     */
    public function sendNewServisNotificationToAdmin(ServisMotor $servisMotor)
    {
        try {
            if (!$this->messaging) {
                Log::warning('Firebase messaging not available');
                return false;
            }

            $message = CloudMessage::withTarget('topic', 'admin_notifications')
                ->withNotification(Notification::create(
                    '🔔 Pengajuan Servis Baru!',
                    "Motor {$servisMotor->no_kendaraan} - {$servisMotor->keluhan}"
                ))
                ->withData([
                    'type' => 'new_servis',
                    'servis_id' => (string)$servisMotor->id,
                    'no_kendaraan' => $servisMotor->no_kendaraan,
                    'jenis_motor' => $servisMotor->jenis_motor,
                    'keluhan' => $servisMotor->keluhan,
                    'status' => $servisMotor->status,
                    'user_name' => $servisMotor->user->name ?? 'Unknown',
                    'created_at' => $servisMotor->created_at->toISOString(),
                    'click_action' => 'SERVIS_DETAIL',
                ]);

            $this->messaging->send($message);

            Log::info('Admin notification sent successfully', [
                'servis_id' => $servisMotor->id,
                'topic' => 'admin_notifications'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent servis for dashboard (untuk polling jika perlu)
     */
    public function getRecentServis(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $recentServis = ServisMotor::with('user:id,nama')
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($servis) {
                    return [
                        'id' => $servis->id,
                        'no_kendaraan' => $servis->no_kendaraan,
                        'jenis_motor' => $servis->jenis_motor,
                        'keluhan' => $servis->keluhan,
                        'status' => $servis->status,
                        'user_name' => $servis->user->nama ?? 'Unknown',
                        'created_at' => $servis->created_at->toIso8601String(),
                        'formatted_date' => $servis->created_at->format('d M Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentServis
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting recent servis: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data'
            ], 500);
        }
    }


    public function subscribeToTopic(Request $request)
    {
        try {
            $token = $request->input('token');
            $topic = $request->input('topic', 'admin_notifications');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak ditemukan'
                ], 400);
            }

            if (!$this->messaging) {
                Log::warning('Firebase messaging not initialized');
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase messaging tidak tersedia'
                ], 500);
            }

            $this->messaging->subscribeToTopic($topic, $token);

            Log::info('Admin subscribed to topic', [
                'topic' => $topic,
                'token' => substr($token, 0, 20) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil subscribe ke topic {$topic}",
                'topic' => $topic
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to topic: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal subscribe ke topic: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnreadNotifications(Request $request)
    {
        try {
            $notifications = AdminNotification::unread()
                ->with('notifiable')
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($notif) {
                    return [
                        'id' => $notif->id,
                        'type' => $notif->type,
                        'title' => $notif->title,
                        'message' => $notif->message,
                        'data' => $notif->data,
                        'action_url' => $notif->action_url,
                        'formatted_date' => $notif->created_at->locale('id')->format('d M Y H:i'),
                        'time_ago' => $notif->created_at->locale('id')->diffForHumans(),
                        'created_at' => $notif->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'count' => $notifications->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil notifikasi'
            ], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        try {
            $count = AdminNotification::unread()->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'count' => 0
            ], 500);
        }
    }

    /**
     * Mark single notification as read (dan DELETE dari database)
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = AdminNotification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan'
                ], 404);
            }

            // DELETE notifikasi setelah dibaca
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai sebagai dibaca dan dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai notifikasi'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read (DELETE semua)
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $count = AdminNotification::unread()->count();

            AdminNotification::unread()->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} notifikasi berhasil ditandai sebagai dibaca dan dihapus",
                'deleted_count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai semua notifikasi'
            ], 500);
        }
    }
}
