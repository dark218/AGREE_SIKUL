<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class NotifyMail
{
    use Queueable, SerializesModels;
    public $email;
    public $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email,$message,$login)
    {
        $this->email = $email;
        $this->message = $message;
        $this->login = $login;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Notification Transactions AGREE SIKUL - '.$this->login)
            ->markdown('Email.notify')->with([
                'email' => $this->email,
                'message' => $this->message,
            ]);
    }
}
