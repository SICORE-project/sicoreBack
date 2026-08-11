<?php

namespace App\Mail\Indemnites;

use App\Models\Convocations as ConvocationModel;
use App\Models\Personnel\Enseignant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Remplace l'ancien Mail::raw() utilisé dans ConvocationEnvoiController.
 * Permet un template d'email cohérent (vue Blade) au lieu de texte brut.
 */
class ConvocationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConvocationModel $convocation,
        public Enseignant $enseignant,
        public ?string $messagePersonnalise = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convocation : ' . $this->convocation->objet,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.convocation',
            with: [
                'convocation' => $this->convocation,
                'enseignant' => $this->enseignant,
                'messagePersonnalise' => $this->messagePersonnalise,
            ],
        );
    }
}
