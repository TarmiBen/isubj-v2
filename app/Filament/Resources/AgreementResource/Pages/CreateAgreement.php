<?php

namespace App\Filament\Resources\AgreementResource\Pages;

use App\Filament\Resources\AgreementResource;
use App\Models\AgreementInstallment;
use App\Models\PaymentOrder;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAgreement extends CreateRecord
{
    protected static string $resource = AgreementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']  = auth()->id();
        $data['paid_amount'] = 0;
        unset($data['payment_order_ids']); // campo virtual, no es columna de agreements
        return $data;
    }

    protected function afterCreate(): void
    {
        $agreement = $this->record;
        $rawData   = $this->form->getRawState();
        $orderIds  = array_filter((array) ($rawData['payment_order_ids'] ?? []));

        DB::transaction(function () use ($agreement, $orderIds) {

            // 1. Vincular adeudos al convenio y marcarlos como "en convenio"
            if (!empty($orderIds)) {
                PaymentOrder::whereIn('id', $orderIds)->update([
                    'agreement_id' => $agreement->id,
                    'status'       => 'in_agreement',
                ]);

                // Si es extensión de crédito, también actualizar la fecha de vencimiento
                if (in_array($agreement->type, ['credit_extension', 'both']) && $agreement->new_due_date) {
                    PaymentOrder::whereIn('id', $orderIds)->update([
                        'due_date' => $agreement->new_due_date,
                    ]);
                }
            }

            // 2. Generar parcialidades (fix: usar copy() para no mutar la fecha original)
            if (in_array($agreement->type, ['installment_plan', 'both'])
                && $agreement->installments_count
                && $agreement->installment_amount
                && $agreement->first_installment_date)
            {
                $baseDate = $agreement->first_installment_date->copy();

                for ($i = 1; $i <= $agreement->installments_count; $i++) {
                    AgreementInstallment::create([
                        'agreement_id'       => $agreement->id,
                        'installment_number' => $i,
                        'due_date'           => $baseDate->copy()->addMonths($i - 1),
                        'amount'             => $agreement->installment_amount,
                        'paid_amount'        => 0,
                        'status'             => 'pending',
                    ]);
                }
            }
        });
    }
}