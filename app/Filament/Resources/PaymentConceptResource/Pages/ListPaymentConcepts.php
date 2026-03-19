<?php

namespace App\Filament\Resources\PaymentConceptResource\Pages;

use App\Filament\Resources\PaymentConceptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentConcepts extends ListRecords
{
    protected static string $resource = PaymentConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
