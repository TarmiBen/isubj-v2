<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Support\ServiceProvider;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Panel;
use App\Models\Qualification;
use App\Observers\QualificationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observers
        Qualification::observe(QualificationObserver::class);
        // Nota: AlertObserver ya no se usa, la lógica está en CreateAlert y EditAlert

        // ── Rate limiters API alumnos ───────────────────────────────────────
        // Login/registro: por identificador+IP, agresivo, para frenar fuerza bruta.
        RateLimiter::for('student-auth', function (Request $request) {
            $identifier = \Illuminate\Support\Str::lower((string) $request->input('identifier')).'|'.$request->ip();

            return Limit::perMinute(5)->by($identifier)->response(function () {
                return response()->json([
                    'message' => 'Demasiados intentos. Intenta de nuevo en unos minutos.',
                ], 429);
            });
        });

        // Resto de la API de alumnos ya autenticada (token válido).
        RateLimiter::for('student-api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Azúcar sintáctica: $request->student() en lugar de leer el atributo
        // que deja EnsureStudentUser.
        Request::macro('student', function () {
            /** @var \Illuminate\Http\Request $this */
            return $this->attributes->get('student');
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch){
            $switch
                ->locales(['ar', 'en', 'es']);
        });

        // Configuración global para usar dropdown en acciones de tablas
        Table::configureUsing(function (Table $table) {
            return $table
                ->actionsColumnLabel('Acciones')
                ->actionsPosition(ActionsPosition::AfterColumns);
        });

        // Metadata extra para TODOS los modelos con LogsActivity (sin tocar
        // cada uno de los 51 modelos individualmente): de dónde vino el
        // cambio. Útil para auditoría — quién lo hizo ya se captura vía
        // causer, pero no desde qué IP/navegador. En consola (comandos/cron)
        // no hay request real, así que se omite en vez de guardar basura.
        Activity::creating(function (Activity $activity) {
            if (! app()->runningInConsole()) {
                $activity->properties = $activity->properties->merge([
                    'ip_address' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                ]);
            }
        });
    }
}
