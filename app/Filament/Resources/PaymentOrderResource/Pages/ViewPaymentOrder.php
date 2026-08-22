<?php

namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use App\Models\PaymentOrder;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentOrder extends ViewRecord
{
    protected static string $resource = PaymentOrderResource::class;

    public function getTitle(): string
    {
        return "Adeudo {$this->record->folio}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pay')
                ->label('Registrar pago')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['pending', 'partial', 'overdue', 'in_agreement'], true))
                ->modalHeading(fn () => "Registrar pago — Folio {$this->record->folio}")
                ->modalSubmitActionLabel('Registrar pago')
                ->fillForm(fn (): array => [
                    'payment_date'   => now()->format('Y-m-d'),
                    'amount_applied' => $this->record->balance,
                ])
                ->form(fn (): array => PaymentService::paymentFormSchema($this->record))
                ->action(function (array $data): void {
                    try {
                        $result = PaymentService::registerPayment($this->record, $data);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Monto inválido')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    PaymentService::notifyPaymentRegistered($result);
                    $this->refreshFormData([]);
                    $this->record->refresh();
                }),

            Actions\Action::make('viewParent')
                ->label('Ver adeudo original')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => $this->record->is_surcharge && $this->record->parent_payment_order_id)
                ->url(fn () => PaymentOrderResource::getUrl('view', ['record' => $this->record->parent_payment_order_id])),

            Actions\EditAction::make(),
        ];
    }
}
