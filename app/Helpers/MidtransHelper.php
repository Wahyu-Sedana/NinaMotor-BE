<?php

namespace App\Helpers;

class MidtransHelper
{
    public static function mergeTransactionData($local, $midtrans)
    {
        return [
            'order_id' => $local->id,
            'user_id' => $local->user_id,
            'tanggal_transaksi' => $local->tanggal_transaksi,
            'total' => $local->total,
            'status_pembayaran' => $midtrans->transaction_status ?? $local->status_pembayaran,
            'status_transaksi' => $local->status_transaksi,
            'metode_pembayaran' => $local->metode_pembayaran,
            'snap_token' => $local->snap_token,
            'midtrans_data' => $midtrans,
        ];
    }

    public static function formatTransactionData($local)
    {
        return [
            'order_id' => $local->id,
            'user_id' => $local->user_id,
            'tanggal_transaksi' => $local->tanggal_transaksi,
            'total' => $local->total,
            'status_pembayaran' => $local->status_pembayaran,
            'status_transaksi' => $local->status_transaksi,
            'metode_pembayaran' => $local->metode_pembayaran,
            'snap_token' => $local->snap_token,
        ];
    }
}
