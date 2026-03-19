<?php

namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentOrder extends ViewRecord
{
    protected static string $resource = PaymentOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}