<?php

namespace App\Http\Controllers;

use App\DataTables\AdminTransactionDataTable;
use App\Exports\TransaksiExport;
use App\Models\AdminNotification;
use App\Models\ServisMotor;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Midtrans\Config;
use Kreait\Firebase\Factory;

class AdminTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    public function index(AdminTransactionDataTable $dataTable)
    {
        if (request()->ajax()) {
            return $dataTable->ajax();
        }
        return $dataTable->render('admin.transaksi.index', [
            'title' => 'Transaksi'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        $transaksi->load('user');

        $itemsData = [];
        if ($transaksi->items_data) {
            $itemsData = json_decode($transaksi->items_data, true) ?? [];
        }

        return view('admin.transaksi.show', [
            'title' => 'Detail Transaksi',
            'transaksi' => $transaksi,
            'itemsData' => $itemsData
        ]);
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, Transaksi $transaksi)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:pending,berhasil,gagal,expired,cancelled'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $transaksi->status_pembayaran;

            $transaksi->update([
                'status_pembayaran' => $request->status_pembayaran
            ]);

            if ($transaksi->type_pembelian == 1 && $request->status_pembayaran == 'berhasil') {
                $servis = \App\Models\ServisMotor::where('transaksi_id', $transaksi->id)->first();

                if ($servis) {
                    $servis->update([
                        'status' => 'done'
                    ]);

                    $this->sendFirebaseNotification($servis, 'done');

                    Log::info('Servis status auto-updated to done', [
                        'servis_id' => $servis->id,
                        'transaksi_id' => $transaksi->id
                    ]);
                }
            }

            DB::commit();

            Log::info('Transaction status updated', [
                'id' => $transaksi->id,
                'old_status' => $oldStatus,
                'new_status' => $transaksi->status_pembayaran,
                'user' => auth()->user()->nama ?? 'Unknown'
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status pembayaran berhasil diperbarui',
                    'data' => $transaksi
                ]);
            }

            return redirect()->route('admin.transaksi.index')
                ->with('success', 'Status pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating transaction status', [
                'id' => $transaksi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui status'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui status.')
                ->withInput();
        }
    }

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
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        try {
            $allowedStatuses = ['gagal', 'expired', 'cancelled'];

            if (!in_array($transaksi->status_pembayaran, $allowedStatuses)) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hanya transaksi dengan status failed, expired, atau cancelled yang dapat dihapus.'
                    ], 409);
                }

                return redirect()->back()
                    ->with('error', 'Hanya transaksi dengan status failed, expired, atau cancelled yang dapat dihapus.');
            }

            DB::beginTransaction();

            $transaksiData = $transaksi->toArray();
            $transaksi->delete();

            DB::commit();

            Log::info('Transaction deleted', [
                'deleted_data' => $transaksiData,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.transaksi.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting transaction', [
                'id' => $transaksi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus data'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    public function exportExcel(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);
        $bulan = $request->get('bulan');
        $status = $request->get('status');
        $search = $request->get('search');
        $tanggal = now()->format('Y-m-d_H-i-s');

        $filename = "Laporan_Transaksi_{$tanggal}.xlsx";
        return Excel::download(new TransaksiExport($tahun,  $bulan, $status, $search), $filename);
    }

    public function sendNewTransaksiNotificationToAdmin(Transaksi $transaksi)
    {
        try {
            if (!$this->messaging) {
                Log::warning('Firebase messaging not available');
                return false;
            }

            $tipePembelian = $transaksi->type_pembelian == 0 ? 'Sparepart' : 'Servis Motor';
            $status = ucfirst($transaksi->status_pembayaran);

            $message = CloudMessage::withTarget('topic', 'admin_notifications')
                ->withNotification(Notification::create(
                    '💰 Transaksi Baru!',
                    "{$transaksi->user->nama} melakukan pembelian {$tipePembelian} senilai Rp " . number_format($transaksi->total, 0, ',', '.')
                ))
                ->withData([
                    'type' => 'new_transaksi',
                    'transaksi_id' => (string) $transaksi->id,
                    'user_id' => (string) $transaksi->user_id,
                    'user_name' => $transaksi->user->nama ?? 'Unknown',
                    'total' => (string) $transaksi->total,
                    'metode_pembayaran' => $transaksi->metode_pembayaran ?? '-',
                    'status_pembayaran' => $status,
                    'type_pembelian' => $tipePembelian,
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi->toISOString(),
                    'click_action' => 'TRANSAKSI_DETAIL',
                ]);

            $this->messaging->send($message);

            Log::info('Admin transaksi notification sent successfully', [
                'transaksi_id' => $transaksi->id,
                'topic' => 'admin_notifications',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send transaksi notification: ' . $e->getMessage());
            return false;
        }
    }

    public function getRecentTransactions(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $recentTransactions = Transaksi::with('user:id,nama')
                ->latest('tanggal_transaksi')
                ->limit($limit)
                ->get()
                ->map(function ($trx) {
                    return [
                        'id' => $trx->id,
                        'user_name' => $trx->user->nama ?? 'Unknown',
                        'total' => number_format($trx->total, 0, ',', '.'),
                        'metode_pembayaran' => $trx->metode_pembayaran ?? '-',
                        'status_pembayaran' => ucfirst($trx->status_pembayaran),
                        'type_pembelian' => $trx->type_pembelian == 0 ? 'Sparepart' : 'Servis Motor',
                        'tanggal_transaksi' => optional($trx->tanggal_transaksi)->toIso8601String(),
                        'formatted_date' => optional($trx->tanggal_transaksi)->format('d M Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentTransactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting recent transactions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi'
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
