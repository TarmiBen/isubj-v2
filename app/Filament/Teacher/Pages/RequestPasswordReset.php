<?php

namespace App\Filament\Teacher\Pages;

use App\Mail\TeacherPasswordResetMail;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\User;
use Filament\Notifications\Notification;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function getHeading(): string | Htmlable
    {
        return __('Recuperar Contraseña - Profesores');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('Introduce tu dirección de email y te enviaremos un enlace para restablecer tu contraseña.');
    }

    public function request(): void
    {
        $data = $this->form->getState();

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            Notification::make()
                ->title('Email enviado')
                ->body('Si el email existe en nuestro sistema, recibirás un enlace de restablecimiento.')
                ->success()
                ->send();
            return;
        }

        // Verificar que el usuario tenga un teacher asociado
        if (!$user->userable || !($user->userable instanceof \App\Models\Teacher)) {
            Notification::make()
                ->title('Email enviado')
                ->body('Si el email existe en nuestro sistema, recibirás un enlace de restablecimiento.')
                ->success()
                ->send();
            return;
        }

        $status = Password::sendResetLink(['email' => $data['email']], function ($user, $token) {
            // Usar nuestro mailable personalizado
            Mail::to($user->email)->send(new TeacherPasswordResetMail($user, $token));
        });

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Email enviado')
                ->body('Te hemos enviado un enlace para restablecer tu contraseña.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Email enviado')
                ->body('Si el email existe en nuestro sistema, recibirás un enlace de restablecimiento.')
                ->success()
                ->send();
        }
    }
}
