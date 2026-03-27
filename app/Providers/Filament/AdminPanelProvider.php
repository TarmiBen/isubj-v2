<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Tables;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Route;
use BezhanSalleh\FilamentShield\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\PermissionResource;
use Rmsramos\Activitylog\ActivitylogPlugin;



class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandLogo( asset('favicon.ico'))
            ->brandLogoHeight('60px')
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                //ActivitylogPlugin::make(),
            ])

            ->colors([
                'primary' => Color::Cyan,
            ])
            ->databaseNotifications()
            ->discoverResources(app_path('Filament/Resources'), 'App\\Filament\\Resources')
            ->discoverPages(app_path('Filament/Admin/Pages'), 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(app_path('Filament/Widgets'), 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \Filament\Http\Middleware\Authenticate::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn () => new \Illuminate\Support\HtmlString('
                    <link rel="manifest" href="/manifest-admin.json">
                    <meta name="theme-color" content="#ffffff">
                    <meta name="mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="default">
                    <meta name="apple-mobile-web-app-title" content="ISUBJ Admin">
                    <link rel="apple-touch-icon" href="/icons/icon-152x152.png">
                ')
            )
            ->renderHook(
                'panels::body.end',
                fn () => new \Illuminate\Support\HtmlString('
                    <script>
                        if (\'serviceWorker\' in navigator) {
                            window.addEventListener(\'load\', () => {
                                navigator.serviceWorker.register(\'/sw.js\', { scope: \'/admin/\' })
                                    .catch(() => {});
                            });
                        }
                    </script>
                ')
            )
            ->renderHook(
                'panels::body.start',
                function () {
                    // Configuración global para usar dropdown en acciones
                    Tables\Table::configureUsing(function (Tables\Table $table) {
                        return $table->actionsColumnLabel('Acciones');
                    });
                }
            );
    }

}
