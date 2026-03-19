<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends BaseResetPassword
{
    public function getHeading(): string | Htmlable
    {
        return __('Restablecer Contraseña - Profesores');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('Ingresa tu nueva contraseña para completar el restablecimiento.');
    }
}
