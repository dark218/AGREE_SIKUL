<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeleteAccountMail extends Mailable
{
    use Queueable, SerializesModels;
    public $nom;
    public $motif;

    /**
     * Create a new message instance.
     */
    public function __construct($nom, $motif)
    {
        $this->nom = $nom;
        $this->motif = $motif;
    }


    public function build()
    {
        return $this->subject("Suppression de votre compte sur " . env('APP_NAME'))
            ->markdown('emails.delete_account')
            ->with([
                'nom' => $this->nom,
                'motif' => $this->motif,
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
