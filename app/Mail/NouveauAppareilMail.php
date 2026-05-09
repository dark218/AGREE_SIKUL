<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauAppareilMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $nom;
    public $device;
    public $deviceId;

    /**
     * Create a new message instance.
     */
    public function __construct($nom,$device,$deviceId)
    {
        $this->nom = $nom;
        $this->device = $device;
        $this->deviceId = $deviceId;
    }

    public function build()
    {
        return $this->subject("Nouvelle connexion sur " . env('APP_NAME'))
            ->markdown('emails.nouveau_appareil')
            ->with([
                'nom' => $this->nom,
                'device' => $this->device,
                'deviceId' => $this->deviceId,
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
