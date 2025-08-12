<?php

namespace App\Notifications;

use App\Models\ServisMotor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServisNotification extends Notification
{
    use Queueable;

    public $servisMotor;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ServisMotor $servisMotor)
    {
        $this->servisMotor = $servisMotor;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database']; // Kirim notifikasi melalui channel database
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'servis_id' => $this->servisMotor->id,
            'user_id' => $this->servisMotor->user_id,
            'no_kendaraan' => $this->servisMotor->no_kendaraan,
            'keluhan' => $this->servisMotor->keluhan,
            'message' => 'Pengajuan servis baru dari ' . $this->servisMotor->no_kendaraan,
        ];
    }
}
