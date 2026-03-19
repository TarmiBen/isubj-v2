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
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Genera mensualidades cada mes en el día configurado.
        // El cron corre a las 6am todos los días y el comando evalúa si debe ejecutarse.
        $schedule->command('payments:generate-monthly-fees')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/monthly-fees.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
