<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Support\ServiceProvider;
use Rmsramos\Activitylog\ActivitylogPlugin;

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
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch){
            $switch
                ->locales(['ar', 'en', 'es']);
        });
    }
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                ActivitylogPlugin::make(),
            ]);
    }
}
