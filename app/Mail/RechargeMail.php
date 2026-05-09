<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RechargeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $montant;
    public $libelledevise;
    public $nouveauSolde;

    /**
     * Create a new message instance.
     */
    public function __construct($title, $montant, $libelledevise, $nouveauSolde)
    {
        $this->title = $title;
        $this->montant = $montant;
        $this->libelledevise = $libelledevise;
        $this->nouveauSolde = $nouveauSolde;
    }


    public function build()
    {
        return $this->subject($this->title)
            ->markdown('emails.recharge')
            ->with([
                'montant' => $this->montant,
                'libelledevise' => $this->libelledevise,
                'nouveauSolde' => $this->nouveauSolde,
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
