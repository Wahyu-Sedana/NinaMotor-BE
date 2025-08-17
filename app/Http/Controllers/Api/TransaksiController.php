<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            $metodePembayaran = $request->metode_pembayaran ?? 'cash';
            $snapToken = null;

            if ($metodePembayaran !== 'cash') {
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
                            'country_code' => 'IDN'
                        ]
                    ],
                    'enabled_payments' => [
                        'mandiri_va',
                        'bca_va',
                        'bni_va',
                        'bri_va',
                        'qris'
                    ],
                    'credit_card' => [
                        'secure' => true,
                        'save_card' => false
                    ],
                    'expiry' => [
                        'start_time' => date('Y-m-d H:i:s O'),
                        'unit' => 'minutes',
                        'duration' => 60
                    ]
                ];

                $snapToken = Snap::getSnapToken($params);
            }

            $transaksi = Transaksi::create([
                'id' => $tempOrderId,
                'user_id' => $request->user_id,
                'tanggal_transaksi' => now(),
                'total' => $request->total,
                'metode_pembayaran' => $metodePembayaran,
                'status_pembayaran' => 'pending',
                'snap_token' => $snapToken,
                'items_data' => json_encode($request->cart_items),
                'va_number' => null,
                'bank' => null,
            ]);

            DB::commit();

            Log::info('Transaction created successfully', [
                'order_id' => $tempOrderId,
                'user_id' => $request->user_id,
                'total' => $request->total,
                'payment_method' => $metodePembayaran
            ]);

            $responseData = [
                'success' => true,
                'message' => 'Transaction created successfully',
                'order_id' => $tempOrderId,
                'total' => $request->total,
                'status' => 'pending'
            ];

            if ($metodePembayaran !== 'cash') {
                $responseData['snap_token'] = $snapToken;
            }


            $admin = User::where('role', 'admin')->first();

            if ($admin) {
                $admin->notify(new NewTransactionNotification($transaksi));
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
                'transaction_id' => $transaksi->id,
                'order_id' => $transaksi->id,
                'status' => $transaksi->status_pembayaran,
                'amount' => (string) $transaksi->total,
                'type' => 'payment_update',
            ];

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create(
                    $notificationData['title'],
                    $notificationData['body']
                ))
                ->withData($additionalData);

            $this->messaging->send($message);
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
        }
    }

    private function getNotificationContent($transaksi)
    {
        $amount = 'Rp ' . number_format($transaksi->total ?? 0, 0, ',', '.');
        $orderId = $transaksi->id;

        switch ($transaksi->status_pembayaran) {
            case 'berhasil':
                return [
                    'title' => 'Pembayaran Berhasil!',
                    'body' => "Pembayaran order #{$orderId} sebesar {$amount} berhasil!"
                ];
            case 'pending':
                return [
                    'title' => 'Menunggu Pembayaran',
                    'body' => "Order #{$orderId} sebesar {$amount} menunggu pembayaran."
                ];
            case 'gagal':
                return [
                    'title' => 'Pembayaran Gagal',
                    'body' => "Pembayaran order #{$orderId} gagal. Silakan coba lagi."
                ];
            case 'expired':
                return [
                    'title' => 'Pembayaran Expired',
                    'body' => "Pembayaran order #{$orderId} telah expired."
                ];
            default:
                return [
                    'title' => 'Update Transaksi',
                    'body' => "Status order #{$orderId} diperbarui."
                ];
        }
    }

    // public function continuePayment(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'order_id' => 'required|string',
    //         'user_id' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         $transaksi = Transaksi::where('id', $request->order_id)
    //             ->where('user_id', $request->user_id)
    //             ->first();

    //         if (!$transaksi) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Transaction not found'
    //             ], 404);
    //         }

    //         if ($transaksi->status_pembayaran !== 'pending') {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Transaction is not pending',
    //                 'current_status' => $transaksi->status_pembayaran
    //             ], 400);
    //         }

    //         if (!$transaksi->snap_token) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Snap token not found'
    //             ], 400);
    //         }

    //         try {
    //             $status = MidtransTransaction::status($transaksi->id);

    //             if ($status->transaction_status !== 'pending') {
    //                 $oldStatus = $transaksi->status_pembayaran;

    //                 switch ($status->transaction_status) {
    //                     case 'capture':
    //                     case 'settlement':
    //                         $transaksi->status_pembayaran = 'berhasil';
    //                         break;
    //                     case 'expire':
    //                         $transaksi->status_pembayaran = 'expired';
    //                         break;
    //                     case 'deny':
    //                     case 'cancel':
    //                         $transaksi->status_pembayaran = 'gagal';
    //                         break;
    //                 }

    //                 if (!empty($status->payment_type)) {
    //                     $transaksi->metode_pembayaran = $status->payment_type;
    //                 }

    //                 if (!empty($status->va_numbers)) {
    //                     $transaksi->va_number = $status->va_numbers[0]->va_number ?? null;
    //                     $transaksi->bank = $status->va_numbers[0]->bank ?? null;
    //                 }

    //                 $transaksi->save();

    //                 if ($oldStatus !== $transaksi->status_pembayaran) {
    //                     $this->sendFirebaseNotification($transaksi, $status);
    //                 }

    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Transaction status has changed',
    //                     'current_status' => $transaksi->status_pembayaran
    //                 ], 400);
    //             }
    //         } catch (\Exception $e) {
    //             Log::warning('Failed to check Midtrans status: ' . $e->getMessage());
    //         }

    //         $isProduction = config('services.midtrans.is_production');
    //         $baseUrl = $isProduction
    //             ? 'https://app.midtrans.com/snap/v2/vtweb/'
    //             : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

    //         $paymentUrl = $baseUrl . $transaksi->snap_token;

    //         Log::info('Continue payment requested', [
    //             'order_id' => $transaksi->id,
    //             'user_id' => $request->user_id
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Payment URL generated successfully',
    //             'data' => [
    //                 'order_id' => $transaksi->id,
    //                 'snap_token' => $transaksi->snap_token,
    //                 'payment_url' => $paymentUrl,
    //                 'total' => $transaksi->total,
    //                 'status' => $transaksi->status_pembayaran
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Continue payment failed: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to continue payment',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
