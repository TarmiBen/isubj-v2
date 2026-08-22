<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\Filament\TeacherPanelProvider::class,
         App\Providers\Filament\AdminPanelProvider::class, // si aplica
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Genera mensualidades según la configuración activa (MonthlyFeeConfig).
        //
        // Se corre CADA HORA, no una vez al día: `dailyAt('06:00')` exige que el
        // cron del hosting dispare `schedule:run` exactamente en el minuto 06:00,
        // y cuando no cae ahí el job se salta el día entero (fue justo lo que pasó
        // en agosto 2026). El comando es idempotente — salta las mensualidades que
        // ya existen — así que repetirlo no duplica nada.
        $schedule->command('payments:generate-monthly-fees')
                 ->hourly()
                 ->timezone('America/Mexico_City')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/monthly-fees.log'));

        // Marca vencidos los adeudos cuya fecha límite ya pasó, para que los
        // badges rojos y las alertas de recargo sean consistentes.
        $schedule->command('payments:mark-overdue')
                 ->hourly()
                 ->timezone('America/Mexico_City')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/monthly-fees.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
