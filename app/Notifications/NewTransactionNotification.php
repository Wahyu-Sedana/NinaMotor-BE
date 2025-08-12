<?php

namespace App\Notifications;

use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTransactionNotification extends Notification
{
    use Queueable;

    public $transaksi;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaksi $transaksi)
    {
        $this->transaksi = $transaksi;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // Simpan ke database
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'transaksi_id' => $this->transaksi->id,
            'user_id' => $this->transaksi->user_id,
            'tanggal_transaksi' => $this->transaksi->tanggal_transaksi,
            'total' => $this->transaksi->total,
            'metode_pembayaran' => $this->transaksi->metode_pembayaran,
            'status_pembayaran' => $this->transaksi->status_pembayaran,
            'message' => 'Transaksi baru dari user ID: ' . $this->transaksi->user_id,
        ];
    }
}
