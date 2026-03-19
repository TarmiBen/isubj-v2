<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Restablecimiento de Contraseña - Panel de Profesores',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.teacher-password-reset',
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

    /**
     * Build the message (método alternativo si no funciona el anterior)
     */
    public function build()
    {
        $resetUrl = route('filament.teacher.auth.password-reset.reset', [
            'token' => $this->token,
            'email' => $this->user->email,
        ]);

        return $this->subject('Restablecimiento de Contraseña - Panel de Profesores')
                    ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #333;'>Restablecimiento de Contraseña</h2>

                <p>Hola {$this->user->name},</p>

                <p>Has solicitado restablecer tu contraseña para el panel de profesores. Haz clic en el siguiente enlace para continuar:</p>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}'
                       style='background-color: #f59e0b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Restablecer Contraseña
                    </a>
                </div>

                <p><strong>Este enlace expirará en 60 minutos.</strong></p>

                <p>Si no solicitaste este restablecimiento, puedes ignorar este correo.</p>

                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>

                <p style='font-size: 12px; color: #666;'>
                    Si tienes problemas haciendo clic en el botón, copia y pega la siguiente URL en tu navegador:<br>
                    <a href='{$resetUrl}'>{$resetUrl}</a>
                </p>

                <p style='font-size: 12px; color: #666;'>
                    Saludos,<br>
                    Sistema de Gestión Académica
                </p>
            </div>
        ");
    }
}
