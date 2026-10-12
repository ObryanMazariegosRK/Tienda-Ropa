<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

//Correo que se envía desde el panel para comprobar que una cuenta de envío funciona
class MailSenderTestMail extends Mailable
{
    public function __construct(
        public string $fromName,
        public string $fromEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Correo de prueba de la tienda');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.mail-sender-test');
    }
}
