<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Models\Payment;
use App\Models\PaymentOrder;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class PaymentsOverview extends Widget
{
    protected static string $view = 'filament.resources.payment-resource.widgets.payments-overview';

    protected int | string | array $columnSpan = 'full';

    public string $period = 'this_month';

    public ?string $dateFrom = null;

    public ?string $dateUntil = null;

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->dateFrom  = null;
            $this->dateUntil = null;
        }
    }

    protected function getRange(): array
    {
        $now = now();

        return match ($this->period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : $now->copy()->startOfMonth(),
                $this->dateUntil ? Carbon::parse($this->dateUntil)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    public function getStats(): array
    {
        [$from, $to] = $this->getRange();

        $paymentsInRange = Payment::query()
            ->whereBetween('payment_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->with(['orders', 'method'])
            ->get();

        $totalPendiente = PaymentOrder::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance');

        $totalVencido = PaymentOrder::query()
            ->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($q2) {
                        $q2->whereIn('status', ['pending', 'partial'])->where('due_date', '<', now());
                    });
            })
            ->sum('balance');

        // Recargos por mora: son PaymentOrder con is_surcharge = true, así que
        // se pueden agregar sin tocar los pagos.
        $surchargesInRange = PaymentOrder::query()
            ->where('is_surcharge', true)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from, $to]);

        return [
            'from'             => $from,
            'to'               => $to,
            'surcharge_generado' => (clone $surchargesInRange)->sum('total'),
            'surcharge_cobrado'  => (clone $surchargesInRange)->sum('paid_amount'),
            'surcharge_count'    => (clone $surchargesInRange)->count(),
            'surcharge_pendiente' => PaymentOrder::query()
                ->where('is_surcharge', true)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->sum('balance'),
            'total_cobrado'    => $paymentsInRange->sum('amount_applied'),
            'total_recibido'   => $paymentsInRange->sum('amount_received'),
            'count_pagos'      => $paymentsInRange->count(),
            'count_completos'  => $paymentsInRange->filter(fn (Payment $p) => $p->coverage_type === 'completo')->count(),
            'count_parciales'  => $paymentsInRange->filter(fn (Payment $p) => $p->coverage_type === 'parcial')->count(),
            'count_anticipos'  => $paymentsInRange->filter(fn (Payment $p) => $p->coverage_type === 'anticipo')->count(),
            'by_method'        => $paymentsInRange
                ->groupBy(fn (Payment $p) => $p->method?->name ?? 'Sin método')
                ->map(fn ($group) => $group->sum('amount_applied'))
                ->sortDesc(),
            'total_pendiente'  => $totalPendiente,
            'total_vencido'    => $totalVencido,
        ];
    }
}