<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryMail extends Mailable
{   
    //Para ejecutarlo en segundo plano por asi decirlo xd
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de contraseña',
        );
    }

    public function content(): Content
    {
        //Esta será la vista Blade para el correo de recuperación
        return new Content(
            view: 'emails.passwordRecovery',
        );
    }
}