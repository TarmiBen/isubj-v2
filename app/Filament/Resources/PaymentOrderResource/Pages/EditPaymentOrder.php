<?php

namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentOrder extends EditRecord
{
    protected static string $resource = PaymentOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax      = (float) ($data['tax_amount'] ?? 0);
        $total    = max(0, $subtotal - $discount + $tax);

        $data['total']   = $total;
        $data['balance'] = max(0, $total - (float) $this->record->paid_amount);

        return $data;
    }
}