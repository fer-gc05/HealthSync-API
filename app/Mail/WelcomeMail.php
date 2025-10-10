<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;


    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct(public string $userName, public string $userEmail)
    {}

    /**
     * Obtener el envelope del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a SaludOne!',
        );
    }

    /**
     * Obtener la definición del contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName' => $this->userName,
                'userEmail' => $this->userEmail
            ]
        );
    }

    /**
     * Obtener los archivos adjuntos del mensaje.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
