<?php

namespace App\Filament\Support;

use App\Models\PaymentOrder;
use Filament\Actions\Action as PageAction;
use Filament\Tables\Actions\Action as TableAction;

/**
 * Vista de "Historial de abonos" de un adeudo, reutilizable desde tablas
 * (Adeudos, relation manager del alumno) y desde páginas (vista del alumno).
 */
class PaymentOrderHistory
{
    public const VIEW = 'filament.payments.order-history';

    /** Acción de tabla: el registro es el propio PaymentOrder. */
    public static function tableAction(string $name = 'history'): TableAction
    {
        return TableAction::make($name)
            ->label('Historial')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalHeading(fn (PaymentOrder $record) => "Historial de abonos — {$record->folio}")
            ->modalDescription(fn (PaymentOrder $record) => $record->student?->full_name)
            ->modalContent(fn (PaymentOrder $record) => view(static::VIEW, ['order' => $record]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    /**
     * Acción de página: el adeudo llega por argumento, igual que el patrón
     * `mountAction('pay', { orderId: ... })` que ya usa ViewStudent.
     */
    public static function pageAction(string $name = 'history'): PageAction
    {
        return PageAction::make($name)
            ->label('Historial')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalHeading(function (array $arguments): string {
                $order = PaymentOrder::find($arguments['orderId'] ?? null);

                return 'Historial de abonos' . ($order ? " — {$order->folio}" : '');
            })
            ->modalContent(fn (array $arguments) => view(static::VIEW, [
                'order' => PaymentOrder::findOrFail($arguments['orderId']),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}
