<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class SendMailreset
{
    use Queueable, SerializesModels;
    public $token;
    public $email;
    public $login;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token, $email,$nom,$login,$message)
    {
        $this->token = $token;
        $this->nom = $nom;
        $this->email = $email;
        $this->login = $login;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Notification utilisateur SmilPay | '.$this->login)
            ->markdown('Email.passwordReset')->with([
                'token' => $this->token,
                'nom' => $this->nom,
                'message' => $this->message,
                'email' => $this->email,
                'login' => $this->login,
            ]);
    }
}
