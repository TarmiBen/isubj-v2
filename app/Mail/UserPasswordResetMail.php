<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserPasswordResetMail extends Mailable
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
            subject: 'Restablecimiento de Contraseña - Sistema de Gestión',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.user-password-reset',
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
        // Determinar la URL de reset basándose en el tipo de usuario
        $resetUrl = $this->getResetUrl();

        return $this->subject('Restablecimiento de Contraseña - Sistema de Gestión')
                    ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>Restablecimiento de Contraseña</h1>
                    <p style='color: white; margin: 10px 0 0 0; opacity: 0.9;'>Sistema de Gestión</p>
                </div>

                <div style='padding: 0 20px;'>
                    <p style='font-size: 16px; margin-bottom: 20px;'>
                        Hola <strong>{$this->user->name}</strong>,
                    </p>

                    <p style='margin-bottom: 20px;'>
                        Has solicitado restablecer tu contraseña. Para continuar con el proceso, haz clic en el siguiente botón:
                    </p>

                    <div style='text-align: center; margin: 40px 0;'>
                        <a href='{$resetUrl}'
                           style='background-color: #3b82f6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                            Restablecer mi Contraseña
                        </a>
                    </div>

                    <div style='background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 30px 0; border-radius: 4px;'>
                        <p style='margin: 0; font-size: 14px;'>
                            <strong>⚠️ Importante:</strong> Este enlace expirará en <strong>60 minutos</strong> por razones de seguridad.
                        </p>
                    </div>

                    <p style='margin-bottom: 20px; font-size: 14px; color: #666;'>
                        Si no solicitaste este restablecimiento de contraseña, puedes ignorar este correo de forma segura. Tu contraseña no será modificada.
                    </p>
                </div>

                <hr style='margin: 40px 20px; border: none; border-top: 1px solid #e5e7eb;'>

                <div style='padding: 0 20px;'>
                    <p style='font-size: 12px; color: #9ca3af; line-height: 1.4;'>
                        <strong>¿Problemas con el botón?</strong><br>
                        Si tienes dificultades haciendo clic en el botón de arriba, copia y pega la siguiente URL en tu navegador:
                    </p>
                    <p style='font-size: 11px; color: #6b7280; word-break: break-all; background-color: #f9fafb; padding: 10px; border-radius: 4px; margin: 10px 0;'>
                        {$resetUrl}
                    </p>

                    <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <p style='font-size: 12px; color: #9ca3af; margin: 0;'>
                            Saludos,<br>
                            <strong>Sistema de Gestión Académica</strong>
                        </p>
                    </div>
                </div>
            </div>
        ");
    }

    /**
     * Determinar la URL de reset basándose en el tipo de usuario
     */
    private function getResetUrl(): string
    {
        // Si el usuario tiene una relación con Teacher, usar la URL del panel de teacher
        if ($this->user->userable_type === 'App\\Models\\Teacher') {
            return route('filament.teacher.auth.password-reset.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ]);
        }

        // Para otros tipos de usuario, usar la URL del panel admin
        try {
            return route('filament.admin.auth.password-reset.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ]);
        } catch (\Exception $e) {
            // Si no existe el panel admin, usar el de teacher como fallback
            return route('filament.teacher.auth.password-reset.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ]);
        }
    }
}
