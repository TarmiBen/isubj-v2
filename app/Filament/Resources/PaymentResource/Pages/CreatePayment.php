<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\PaymentOrderPayment;
use App\Models\PaymentReference;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']   = auth()->id();
        $data['received_by']  = auth()->id();
        $data['status']       = 'applied';

        // Calcular amount_applied desde los order_payments
        $orderPayments = $data['order_payments'] ?? [];
        $amountApplied = collect($orderPayments)->sum('amount_applied');
        $amountReceived = (float) ($data['amount_received'] ?? 0);

        $data['amount_applied'] = $amountApplied;
        $data['change_amount']  = max(0, $amountReceived - $amountApplied);

        // Limpiar campos que no son de la tabla payments
        unset($data['order_payments'], $data['reference']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $payment = $this->record;
        $rawData = $this->form->getRawState();

        // Aplicar a cada adeudo
        DB::transaction(function () use ($payment, $rawData) {
            foreach ($rawData['order_payments'] ?? [] as $item) {
                PaymentOrderPayment::create([
                    'payment_id'       => $payment->id,
                    'payment_order_id' => $item['payment_order_id'],
                    'amount_applied'   => $item['amount_applied'],
                ]);

                $order = \App\Models\PaymentOrder::find($item['payment_order_id']);
                $order?->applyPayment((float) $item['amount_applied']);
            }

            // Referencia bancaria
            $ref = $rawData['reference'] ?? [];
            if (!empty($ref['reference_number'])) {
                PaymentReference::create([
                    'payment_id'       => $payment->id,
                    'reference_number' => $ref['reference_number'],
                    'bank'             => $ref['bank'] ?? null,
                    'receipt_path'     => $ref['receipt_path'] ?? null,
                ]);
            }

            $payment->update(['status' => 'applied']);
        });
    }
}