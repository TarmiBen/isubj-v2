<?php

namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentOrder extends CreateRecord
{
    protected static string $resource = PaymentOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax      = (float) ($data['tax_amount'] ?? 0);
        $total    = max(0, $subtotal - $discount + $tax);
        $data['total']       = $total;
        $data['paid_amount'] = 0;
        $data['balance']     = $total;
        return $data;
    }
}