<?php

namespace App\Console\Commands;

use App\Models\PaymentOrder;
use Illuminate\Console\Command;

class MarkOverduePaymentOrders extends Command
{
    protected $signature = 'payments:mark-overdue {--dry-run : Sólo simula, no guarda}';

    protected $description = 'Marca como vencidos los adeudos pendientes/parciales cuya fecha de vencimiento ya pasó';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // El vencimiento efectivo suma los días extra del convenio vigente del
        // alumno (si lo hay), así que la comparación se hace contra
        // due_date + extra_days y no contra due_date a secas.
        $query = PaymentOrder::query()
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance', '>', 0)
            ->whereRaw(
                'DATE_ADD(payment_orders.due_date, INTERVAL COALESCE(('
                . 'SELECT a.extra_days FROM agreements a'
                . '  WHERE a.student_id = payment_orders.student_id'
                . "    AND a.status = 'active'"
                . "    AND a.type IN ('credit_extension','both')"
                . '    AND a.extra_days > 0'
                . '    AND a.deleted_at IS NULL'
                . '  ORDER BY a.created_at DESC LIMIT 1'
                . '), 0) DAY) < CURDATE()'
            );

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No hay adeudos por marcar como vencidos.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[DRY-RUN] Se marcarían {$count} adeudo(s) como vencidos:");

            (clone $query)->with('student.activeAgreements')->limit(20)->get()->each(function (PaymentOrder $order) {
                $extra = $order->agreementExtraDays();

                $this->line(sprintf(
                    '  %s — %s — vence %s%s — saldo $%s',
                    $order->folio,
                    $order->student?->full_name ?? 's/alumno',
                    $order->effectiveDueDate()?->format('d/m/Y') ?? '—',
                    $extra > 0
                        ? sprintf(' (convenio: %s + %d días)', $order->due_date->format('d/m/Y'), $extra)
                        : '',
                    number_format($order->balance, 2),
                ));
            });

            return self::SUCCESS;
        }

        $updated = $query->update(['status' => 'overdue']);

        $this->info("Se marcaron {$updated} adeudo(s) como vencidos.");

        return self::SUCCESS;
    }
}
