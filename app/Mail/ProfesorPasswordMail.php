<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfesorPasswordMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $plainPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }

    /**
     * Get the message envelope.
     */

    public function build()
    {
        return $this->subject('Tu acceso a la plataforma')->html("
            <p>Hola {$this->user->name},</p>
            <p>Tu cuenta ha sido creada correctamente. Aquí están tus datos de acceso:</p>
            <ul>
                <li><strong>Email:</strong> {$this->user->email}</li>
                <li><strong>Contraseña:</strong> {$this->plainPassword}</li>
            </ul>
            <p>Por favor cambia tu contraseña al iniciar sesión.</p>
            <p>Saludos,<br>Equipo de la Plataforma</p>
        ");
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Profesor Password Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
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
