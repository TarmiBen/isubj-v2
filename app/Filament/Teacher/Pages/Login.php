<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Setting;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        if (Setting::get('block_teacher_login', false)) {
            throw ValidationException::withMessages([
                'data.email' => 'El acceso de maestros está temporalmente deshabilitado.',
            ]);
        }

        return parent::authenticate();
    }

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
