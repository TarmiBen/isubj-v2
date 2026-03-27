<?php

namespace App\Providers\Filament;

use App\Filament\Teacher\Pages\Login;
use App\Filament\Teacher\Pages\RequestPasswordReset;
use App\Filament\Teacher\Pages\ResetPassword;
use App\Services\ReservationService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Route;

class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('teacher')
            ->login(Login::class)
            ->passwordReset([
                'request' => RequestPasswordReset::class,
                'reset' => ResetPassword::class,
            ])
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(
                in: app_path('Filament/Teacher/Resources'),
                for: 'App\\Filament\\Teacher\\Resources',
            )
            ->discoverPages(in: app_path('Filament/Teacher/Pages'), for: 'App\\Filament\\Teacher\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Teacher/Widgets'), for: 'App\\Filament\\Teacher\\Widgets')
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
            ])
            ->renderHook(
                'panels::head.end',
                fn () => new \Illuminate\Support\HtmlString('
                    <link rel="manifest" href="/manifest-teacher.json">
                    <meta name="theme-color" content="#ffffff">
                    <meta name="mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="default">
                    <meta name="apple-mobile-web-app-title" content="ISUBJ Docente">
                    <link rel="apple-touch-icon" href="/icons/icon-152x152.png">
                ')
            )
            ->renderHook(
                'panels::body.end',
                fn () => new \Illuminate\Support\HtmlString('
                    <script>
                        if (\'serviceWorker\' in navigator) {
                            window.addEventListener(\'load\', () => {
                                navigator.serviceWorker.register(\'/sw.js\', { scope: \'/teacher/\' })
                                    .catch(() => {});
                            });
                        }
                    </script>
                ')
            )
            ->routes(function () {
                Route::post('/api/scan', function (\Illuminate\Http\Request $request) {
                    try {
                        $request->validate(['qr_code' => 'required|uuid']);
                        $result = app(ReservationService::class)->processQrScan(
                            $request->input('qr_code'),
                            auth()->user(),
                            $request->file('photo')
                        );
                        $action = $result['action'] === 'check_in' ? 'Entrada registrada' : 'Salida registrada';
                        return response()->json([
                            'success' => true,
                            'action' => $action,
                            'message' => "✓ {$action} correctamente."
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage()
                        ], 422);
                    }
                })
                ->middleware(['auth'])
                ->name('filament.teacher.api.scan');
            });
    }
}
