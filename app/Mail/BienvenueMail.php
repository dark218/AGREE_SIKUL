<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenueMail extends Mailable
{
    use Queueable, SerializesModels;
    public $nom;

    /**
     * Create a new message instance.
     */
    public function __construct($nom)
    {
        $this->nom = $nom;
    }


    public function build()
    {
        return $this->subject("Bienvenue sur " . env('APP_NAME'))
            ->markdown('emails.bienvenue')
            ->with([
                'nom' => $this->nom
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
