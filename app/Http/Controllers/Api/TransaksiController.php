<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Illuminate\Support\Str;
use Midtrans\Snap;

class TransaksiController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;
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
                'status_transaksi' => 'pending',
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

    public function midtransCallback(Request $request)
    {
        $notif = new \Midtrans\Notification();

        $transaksi = Transaksi::find($notif->order_id);

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        switch ($notif->transaction_status) {
            case 'capture':
            case 'settlement':
                $transaksi->status_pembayaran = 'berhasil';
                $transaksi->status_transaksi = 'selesai';
                break;

            case 'pending':
                $transaksi->status_pembayaran = 'pending';
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
                $transaksi->status_pembayaran = $notif->transaction_status == 'expire' ? 'expired' : 'gagal';
                $transaksi->status_transaksi = 'dibatalkan';
                break;
        }

        $transaksi->save();

        return response()->json(['message' => 'Callback diproses'], 200);
    }
}
