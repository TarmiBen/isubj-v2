<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Resources\PaymentOrderResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Widgets\PaymentsOverview;
use Filament\Pages\Page;

class PaymentsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard de Pagos';
    protected static ?string $title = 'Dashboard de Pagos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.payments-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_payment')
            || auth()->user()->can('view_any_payment::order');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PaymentsOverview::class,
        ];
    }

    public function getPaymentOrdersUrl(): ?string
    {
        return auth()->user()->can('view_any_payment::order')
            ? PaymentOrderResource::getUrl()
            : null;
    }

    public function getPaymentsUrl(): ?string
    {
        return auth()->user()->can('view_any_payment')
            ? PaymentResource::getUrl()
            : null;
    }
}
