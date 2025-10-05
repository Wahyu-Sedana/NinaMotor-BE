<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        // URL ini akan dibuka di app Flutter
        $resetUrl = url('/api/reset-password?token=' . $this->token);

        return $this->subject('Reset Password Akun Anda')
            ->view('emails.password-reset')
            ->with([
                'userName' => $this->user->nama,
                'resetUrl' => $resetUrl,
                'token' => $this->token,
            ]);
    }
}
