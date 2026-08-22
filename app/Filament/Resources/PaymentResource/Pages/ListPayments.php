<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Widgets\PaymentsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Registrar pago')];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PaymentsOverview::class,
        ];
    }
}