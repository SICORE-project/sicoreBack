<?php

namespace App\Mail;

use App\Models\Admin\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeacherAccountWelcomeMail extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenue sur SICORE — votre compte enseignant est prêt');
    }

    public function content(): Content
    {
        $base = rtrim(config('services.sicore.frontend_url'), '/');

        return new Content(view: 'emails.teacher-account-welcome', with: [
            'teacher' => $this->user->enseignant,
            'loginUrl' => $base.'/login',
            'setupUrl' => $base.'/forgot-password?'.http_build_query(['email' => $this->user->email, 'welcome' => 1]),
        ]);
    }
}
