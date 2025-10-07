<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Transaksi;
use App\Models\User;
use App\Notifications\NewServisNotification;
use App\Notifications\NewTransactionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;



class TransaksiController extends Controller
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

    public function createTransaction(Request $request)
    {
        $mappedCartItems = collect($request->cart_items)->map(function ($item) {
            return [
                'id' => $item['sparepart_id'] ?? null,
                'nama' => $item['nama_sparepart'] ?? null,
                'harga' => isset($item['harga_jual']) ? (float) $item['harga_jual'] : null,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
            ];
        })->toArray();

        $request->merge(['cart_items' => $mappedCartItems]);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'total' => 'required|numeric|min:1000',
            'metode_pembayaran' => 'nullable|string',
            'nama' => 'required|string',
            'email' => 'required|email',
            'telepon' => 'required|string',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required',
            'cart_items.*.nama' => 'required|string',
            'cart_items.*.harga' => 'required|numeric',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'alamat' => 'nullable|string',
            'type_pembelian' => 'nullable|integer|in:0,1',
            'alamat_id' => 'nullable|string',
            'kurir' => 'nullable|string',
            'service' => 'nullable|string',
            'estimasi' => 'nullable|string',
            'ongkir' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $subtotal = collect($request->cart_items)->sum(fn($item) => $item['harga'] * $item['quantity']);
            $ongkir = $request->input('ongkir', 0);
            $grandTotal = $subtotal + $ongkir;

            if ($grandTotal != $request->total) {
                throw new \Exception("Total amount mismatch: calculated $grandTotal, received {$request->total}");
            }

            $tempOrderId = 'ORD-' . time() . '-' . Str::random(6);
            $metodePembayaran = $request->metode_pembayaran ?? 'cash';
            $snapToken = null;
            $typePembelian = $request->input('type_pembelian', 0);

            // Siapkan item_details untuk Midtrans, termasuk ongkir
            $itemDetails = [];
            foreach ($request->cart_items as $item) {
                $itemDetails[] = [
                    'id' => $item['id'],
                    'price' => (int) $item['harga'],
                    'quantity' => (int) $item['quantity'],
                    'name' => $item['nama'],
                ];
            }

            if ($ongkir > 0) {
                $itemDetails[] = [
                    'id' => 'ongkir',
                    'price' => (int) $ongkir,
                    'quantity' => 1,
                    'name' => 'Ongkir',
                ];
            }

            if ($metodePembayaran !== 'cash') {
                $params = [
                    'transaction_details' => [
                        'order_id' => $tempOrderId,
                        'gross_amount' => (int) $grandTotal,
                    ],
                    'item_details' => $itemDetails,
                    'customer_details' => [
                        'first_name' => $request->nama,
                        'email' => $request->email,
                        'phone' => $request->telepon,
                        'billing_address' => [
                            'address' => $request->alamat ?? 'Default Address',
                            'country_code' => 'IDN'
                        ]
                    ],
                    'enabled_payments' => ['mandiri_va', 'bca_va', 'bni_va', 'bri_va', 'qris'],
                    'credit_card' => ['secure' => true, 'save_card' => false],
                    'expiry' => [
                        'start_time' => date('Y-m-d H:i:s O'),
                        'unit' => 'minutes',
                        'duration' => 60
                    ]
                ];

                $snapToken = Snap::getSnapToken($params);
            }

            // Simpan transaksi ke database
            $transaksi = Transaksi::create([
                'id' => $tempOrderId,
                'user_id' => $request->user_id,
                'tanggal_transaksi' => now(),
                'total' => $grandTotal,
                'metode_pembayaran' => $metodePembayaran,
                'status_pembayaran' => 'pending',
                'snap_token' => $snapToken,
                'items_data' => json_encode($request->cart_items),
                'type_pembelian' => $typePembelian,
                'alamat_id' => $request->input('alamat_id'),
                'kurir' => $request->input('kurir'),
                'service' => $request->input('service'),
                'estimasi' => $request->input('estimasi'),
                'ongkir' => $ongkir,
            ]);

            // Notifikasi admin
            AdminNotification::create([
                'type' => 'new_transaction',
                'notifiable_type' => Transaksi::class,
                'notifiable_id' => $transaksi->id,
                'title' => 'Transaksi Baru',
                'message' => "Transaksi #{$transaksi->id} sebesar Rp " . number_format($transaksi->total) .
                    " (" . ($typePembelian == 0 ? 'Sparepart' : 'Servis Motor') . ")",
                'data' => [
                    'transaksi_id' => $transaksi->id,
                    'total' => $transaksi->total,
                    'user_name' => $transaksi->user->nama ?? 'Unknown',
                    'status' => $transaksi->status_pembayaran,
                    'type_pembelian' => $typePembelian,
                ],
                'action_url' => "/admin/transaksi/{$transaksi->id}",
            ]);

            DB::commit();
            $this->sendAdminWebNotification($transaksi);

            $responseData = [
                'success' => true,
                'message' => 'Transaction created successfully',
                'order_id' => $tempOrderId,
                'total' => $grandTotal,
                'status' => 'pending',
                'type_pembelian' => $typePembelian,
            ];

            if ($metodePembayaran !== 'cash') {
                $responseData['snap_token'] = $snapToken;
            }

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction creation failed: ' . $e->getMessage(), [
                'user_id' => $request->user_id ?? null,
                'payment_method' => $request->metode_pembayaran ?? null,
                'total' => $request->total ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    private function sendAdminWebNotification($transaksi)
    {
        try {
            if (!$this->messaging) {
                Log::warning('Firebase messaging not available for admin notification');
                return false;
            }

            if (!$transaksi->relationLoaded('user')) {
                $transaksi->load('user');
            }

            $message = CloudMessage::withTarget('topic', 'admin_notifications')
                ->withNotification(Notification::create(
                    '💰 Transaksi Baru!',
                    "Order #{$transaksi->id} sebesar Rp " . number_format($transaksi->total, 0, ',', '.')
                ))
                ->withData([
                    'type' => 'new_transaction',
                    'transaksi_id' => (string)$transaksi->id,
                    'order_id' => $transaksi->id,
                    'total' => (string)$transaksi->total,
                    'status' => $transaksi->status_pembayaran,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'user_name' => $transaksi->user->name ?? 'Unknown',
                    'user_id' => $transaksi->user_id,
                    'created_at' => $transaksi->created_at->toISOString(),
                ]);

            $this->messaging->send($message);

            Log::info('Admin web notification sent for transaction', [
                'transaksi_id' => $transaksi->id,
                'total' => $transaksi->total
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send admin web notification for transaction: ' . $e->getMessage());
            return false;
        }
    }

    public function midtransCallback()
    {
        try {
            $notif = new \Midtrans\Notification();

            $transaksi = Transaksi::find($notif->order_id);

            if (!$transaksi) {
                Log::error('Transaction not found for order_id: ' . $notif->order_id);
                return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
            }

            $oldStatus = $transaksi->status_pembayaran;

            switch ($notif->transaction_status) {
                case 'capture':
                case 'settlement':
                    $transaksi->status_pembayaran = 'berhasil';
                    break;
                case 'pending':
                    $transaksi->status_pembayaran = 'pending';
                    break;
                case 'deny':
                case 'cancel':
                case 'expired':
                    $transaksi->status_pembayaran = 'expired';
                    break;
            }

            if (!empty($notif->payment_type)) {
                $transaksi->metode_pembayaran = $notif->payment_type;
            }

            if (!empty($notif->va_numbers)) {
                $transaksi->va_number = $notif->va_numbers[0]->va_number ?? null;
                $transaksi->bank = $notif->va_numbers[0]->bank ?? null;
            }

            $transaksi->save();

            if ($oldStatus !== $transaksi->status_pembayaran) {
                $this->sendFirebaseNotification($transaksi, $notif);
            }

            Log::info('Callback processed successfully', [
                'order_id' => $notif->order_id,
                'old_status' => $oldStatus,
                'new_status' => $transaksi->status_pembayaran
            ]);

            return response()->json(['message' => 'Callback diproses'], 200);
        } catch (\Exception $e) {
            Log::error('Callback processing failed: ' . $e->getMessage());
            return response()->json(['message' => 'Callback processing failed'], 500);
        }
    }

    public function getTransactionList(Request $request)
    {
        $query = Transaksi::query();

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->status) {
            $query->where('status_pembayaran', $request->status);
        }

        $transactions = $query->orderByDesc('tanggal_transaksi')
            ->limit($request->limit ?? 20)
            ->get();

        $results = $transactions->map(function ($trx) {
            $data = $trx->toArray();

            if ($trx->items_data) {
                $data['cart_items'] = json_decode($trx->items_data, true);
            }

            if ($trx->status_pembayaran === 'pending' && $trx->va_number && $trx->bank) {
                $data['payment_instruction'] = [
                    'bank' => strtoupper($trx->bank),
                    'va_number' => $trx->va_number
                ];
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction list retrieved successfully',
            'data' => ['transactions' => $results]
        ]);
    }

    private function sendFirebaseNotification($transaksi, $notif = null)
    {
        try {
            if (!$this->messaging) {
                Log::error('Messaging object is null.');
                return;
            }

            $user = User::find($transaksi->user_id);
            if (!$user || !$user->fcm_token) {
                Log::warning('User or FCM token not found.', ['user_id' => $transaksi->user_id]);
                return;
            }

            Log::info('Attempting to send notification.', [
                'fcm_token' => $user->fcm_token,
                'user_id' => $transaksi->user_id
            ]);

            $notificationData = $this->getNotificationContent($transaksi);

            $additionalData = [
                'transaction_id' => (string) $transaksi->id,
                'order_id' => (string) $transaksi->id,
                'status' => $transaksi->status_pembayaran,
                'amount' => (string) $transaksi->total,
                'type' => 'payment_update',
            ];

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create(
                    $notificationData['title'],
                    $notificationData['body']
                ))
                ->withData($additionalData)
                ->withApnsConfig(
                    ApnsConfig::fromArray([
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $notificationData['title'],
                                    'body' => $notificationData['body'],
                                ],
                                'sound' => 'sound.aiff',
                                'badge' => 1,
                                'mutable-content' => 1,
                                'content-available' => 1,
                            ],
                        ],
                    ])
                )
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'channel ID',
                        ],
                    ])
                );

            $this->messaging->send($message);

            Log::info('Firebase notification sent successfully', [
                'user_id' => $user->id,
                'transaction_id' => $transaksi->id,
                'status' => $transaksi->status_pembayaran,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage(), [
                'transaction_id' => $transaksi->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function getNotificationContent($transaksi)
    {
        $amount = 'Rp ' . number_format($transaksi->total ?? 0, 0, ',', '.');
        $orderId = $transaksi->id;

        switch ($transaksi->status_pembayaran) {
            case 'berhasil':
                return [
                    'title' => 'Pembayaran Berhasil! 🎉',
                    'body' => "Pembayaran order #{$orderId} sebesar {$amount} berhasil!"
                ];
            case 'pending':
                return [
                    'title' => 'Menunggu Pembayaran ⏳',
                    'body' => "Order #{$orderId} sebesar {$amount} menunggu pembayaran."
                ];
            case 'gagal':
                return [
                    'title' => 'Pembayaran Gagal ❌',
                    'body' => "Pembayaran order #{$orderId} gagal. Silakan coba lagi."
                ];
            case 'expired':
                return [
                    'title' => 'Pembayaran Expired ⏰',
                    'body' => "Pembayaran order #{$orderId} telah expired."
                ];
            default:
                return [
                    'title' => 'Update Transaksi',
                    'body' => "Status order #{$orderId} diperbarui."
                ];
        }
    }
}
