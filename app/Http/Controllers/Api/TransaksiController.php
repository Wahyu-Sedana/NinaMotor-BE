<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;



class TransaksiController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-credentials.json'))
                ->withProjectId(config('services.firebase.project_id'));

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    protected function mapStatus($midtransStatus)
    {
        return match ($midtransStatus) {
            'settlement', 'capture' => 'berhasil',
            'pending' => 'pending',
            'expire' => 'expired',
            'cancel', 'deny' => 'gagal',
            default => 'unknown',
        };
    }

    protected function mapTransaction($midtransStatus)
    {
        return match ($midtransStatus) {
            'settlement', 'capture' => 'selesai',
            'pending' => 'pending',
            'expire', 'cancel', 'deny' => 'dibatalkan',
            default => 'unknown',
        };
    }


    public function createTransaction(Request $request)
    {
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

            $calculatedTotal = 0;
            foreach ($request->cart_items as $item) {
                $calculatedTotal += $item['harga'] * $item['quantity'];
            }

            if ($calculatedTotal != $request->total) {
                throw new \Exception('Total amount mismatch');
            }
            $tempOrderId = 'ORD-' . time() . '-' . Str::random(6);

            $itemDetails = [];
            foreach ($request->cart_items as $item) {
                $itemDetails[] = [
                    'id' => $item['id'],
                    'price' => (int) $item['harga'],
                    'quantity' => (int) $item['quantity'],
                    'name' => $item['nama'],
                ];
            }

            $params = [
                'transaction_details' => [
                    'order_id' => $tempOrderId,
                    'gross_amount' => (int) $request->total,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $request->nama,
                    'email' => $request->email,
                    'phone' => $request->telepon,
                    'billing_address' => [
                        'address' => $request->alamat ?? 'Default Address',
                        'city' => 'Jakarta',
                        'postal_code' => '12345',
                        'country_code' => 'IDN'
                    ],
                    'shipping_address' => [
                        'address' => $request->alamat ?? 'Default Address',
                        'city' => 'Jakarta',
                        'postal_code' => '12345',
                        'country_code' => 'IDN'
                    ]
                ],
                'enabled_payments' => [
                    'credit_card',
                    'mandiri_va',
                    'bca_va',
                    'bni_va',
                    'bri_va',
                    'other_va',
                    'gopay',
                    'shopeepay',
                    'qris'
                ],
                'credit_card' => [
                    'secure' => true,
                    'save_card' => false,
                    'channel' => 'migs'
                ],
                'callbacks' => [
                    'finish' => config('app.url') . '/payment/finish?order_id=' . $tempOrderId
                ],
                'expiry' => [
                    'start_time' => date('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => 60
                ],
                'custom_field1' => json_encode($request->cart_items)
            ];


            $snapToken = Snap::getSnapToken($params);

            $transaksi = Transaksi::create([
                'id' => $tempOrderId,
                'user_id' => $request->user_id,
                'tanggal_transaksi' => now(),
                'total' => $request->total,
                'metode_pembayaran' => $request->metode_pembayaran ?? 'midtrans',
                'status_pembayaran' => 'pending',
                'snap_token' => $snapToken,
                'alamat' => $request->alamat,
            ]);

            DB::commit();

            Log::info('Transaction created successfully', [
                'temp_order_id' => $tempOrderId,
                'user_id' => $request->user_id,
                'total' => $request->total,
                'snap_token' => $snapToken
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'snap_token' => $snapToken,
                'temp_order_id' => $tempOrderId,
                'total' => $request->total,
                'status' => 'pending'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Transaction creation failed', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
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
                case 'expire':
                    $transaksi->status_pembayaran = $notif->transaction_status == 'expire' ? 'expired' : 'gagal';
                    break;
            }

            $transaksi->save();

            if ($oldStatus !== $transaksi->status_pembayaran) {
                $this->sendFirebaseNotification($transaksi, $notif);
            }

            Log::info('Midtrans callback processed successfully', [
                'order_id' => $notif->order_id,
                'old_status' => $oldStatus,
                'new_status' => $transaksi->status_pembayaran,
                'transaction_status' => $notif->transaction_status
            ]);

            return response()->json(['message' => 'Callback diproses'], 200);
        } catch (\Exception $e) {
            Log::error('Midtrans callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Send Firebase notification to user
     */
    private function sendFirebaseNotification($transaksi, $notif = null)
    {
        try {
            if (!$this->messaging) {
                Log::warning('Firebase messaging not initialized');
                return;
            }

            $user = User::find($transaksi->user_id);

            if (!$user || !$user->fcm_token) {
                Log::warning('FCM token not found for user: ' . $transaksi->user_id);
                return;
            }

            $notificationData = $this->getNotificationContent($transaksi);

            $additionalData = [
                'transaction_id' => $transaksi->id,
                'order_id' => $transaksi->id,
                'status' => $transaksi->status_pembayaran,
                'amount' => (string) $transaksi->total,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'type' => 'payment_update',
                'timestamp' => now()->toISOString(),
            ];

            if ($notif) {
                $additionalData['payment_type'] = $notif->payment_type ?? '';
                $additionalData['transaction_time'] = $notif->transaction_time ?? '';
                if ($notif->payment_type === 'bank_transfer' && isset($notif->va_numbers)) {
                    $additionalData['va_number'] = $notif->va_numbers[0]->va_number ?? '';
                    $additionalData['bank'] = $notif->va_numbers[0]->bank ?? '';
                }
            }

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create(
                    $notificationData['title'],
                    $notificationData['body']
                ))
                ->withData($additionalData);

            $result = $this->messaging->send($message);

            Log::info('Firebase notification sent successfully', [
                'transaction_id' => $transaksi->id,
                'user_id' => $transaksi->user_id,
                'status' => $transaksi->status_pembayaran,
                'message_id' => $result->target()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Firebase notification', [
                'transaction_id' => $transaksi->id ?? 'unknown',
                'user_id' => $transaksi->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get notification content based on transaction status
     */
    private function getNotificationContent($transaksi)
    {
        $amount = 'Rp ' . number_format($transaksi->total ?? 0, 0, ',', '.');
        $orderId = $transaksi->id;

        switch ($transaksi->status_pembayaran) {
            case 'berhasil':
                return [
                    'title' => 'Pembayaran Berhasil!',
                    'body' => "Pembayaran untuk order #{$orderId} sebesar {$amount} telah berhasil diproses. Terima kasih!"
                ];

            case 'pending':
                return [
                    'title' => 'Menunggu Pembayaran',
                    'body' => "Order #{$orderId} sebesar {$amount} menunggu pembayaran. Segera lakukan pembayaran."
                ];

            case 'gagal':
                return [
                    'title' => 'Pembayaran Gagal',
                    'body' => "Pembayaran untuk order #{$orderId} sebesar {$amount} gagal diproses. Silakan coba lagi."
                ];

            case 'expired':
                return [
                    'title' => 'Pembayaran Expired',
                    'body' => "Waktu pembayaran untuk order #{$orderId} sebesar {$amount} telah habis. Silakan buat pesanan baru."
                ];

            default:
                return [
                    'title' => 'Update Transaksi',
                    'body' => "Status transaksi order #{$orderId} telah diperbarui."
                ];
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

        $transactions = $query->orderByDesc('tanggal_transaksi')->limit(20)->get();


        $results = $transactions->map(function ($trx) {
            try {
                $midtrans = MidtransTransaction::status($trx->id);

                $trx->status_pembayaran = $this->mapStatus($midtrans->transaction_status);
                $trx->save();

                if ($midtrans->transaction_status === 'pending' && !empty($midtrans->va_numbers)) {
                    $bank = $midtrans->va_numbers[0]->bank ?? null;
                    $va_number = $midtrans->va_numbers[0]->va_number ?? null;

                    Log::info('Bank:', [$bank]);
                    Log::info('VA Number:', [$va_number]);

                    return array_merge($trx->toArray(), [
                        'midtrans_data' => $midtrans,
                        'payment_instruction' => [
                            'bank' => strtoupper($bank),
                            'va_number' => $va_number,
                        ],
                    ]);
                }

                return array_merge($trx->toArray(), ['midtrans_data' => $midtrans]);
            } catch (\Exception $e) {
                Log::error('Gagal ambil data Midtrans', [
                    'order_id' => $trx->id,
                    'error' => $e->getMessage()
                ]);
                return $trx;
            }
        });


        return response()->json([
            'success' => true,
            'message' => 'Transaction list retrieved successfully',
            'data' => ['transactions' => $results],
        ]);
    }
}
