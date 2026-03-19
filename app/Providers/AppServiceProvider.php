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
    }
}
