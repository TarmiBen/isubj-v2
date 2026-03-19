<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        return __('Iniciar Sesión - Profesores');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return new HtmlString('
            <div class="text-center mt-4">
                <a href="' . route('filament.teacher.auth.password-reset.request') . '"
                   class="text-sm text-primary-600 hover:text-primary-500 font-medium hover:underline">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        ');
    }
}
