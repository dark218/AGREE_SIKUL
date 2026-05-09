<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $minutes;

    /**
     * Crée une nouvelle instance du Mailable.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
        $this->minutes = env('SMS_OTP_SECONDE_TIME_OUT', 600) / 60;
    }

    /**
     * Construction du message.
     */
    public function build()
    {
        return $this->subject("OTP de confirmation")
            ->markdown('emails.send_otp')
            ->with([
                'otp' => $this->otp,
                'minutes' => $this->minutes
            ]);
    }
}
