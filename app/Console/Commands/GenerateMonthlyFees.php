<?php

namespace App\Console\Commands;

use App\Models\Discount;
use App\Models\DiscountApplication;
use App\Models\Inscription;
use App\Models\MonthlyFee;
use App\Models\MonthlyFeeConfig;
use App\Models\PaymentConcept;
use App\Models\PaymentOrder;
use App\Models\Referral;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyFees extends Command
{
    protected $signature = 'payments:generate-monthly-fees
                            {--month= : Mes a generar (1-12). Por defecto: mes actual}
                            {--year=  : Año a generar. Por defecto: año actual}
                            {--config= : ID de configuración específica}
                            {--dry-run : Sólo simula, no guarda}';

    protected $description = 'Genera mensualidades automáticas según configuración (MonthlyFeeConfig)';

    public function handle(): int
    {
        $month   = (int) ($this->option('month') ?: now()->month);
        $year    = (int) ($this->option('year')  ?: now()->year);
        $dryRun  = $this->option('dry-run');
        $configId= $this->option('config');

        $this->info("Generando mensualidades para {$month}/{$year}" . ($dryRun ? ' [DRY-RUN]' : ''));

        // Obtener el usuario sistema (id=1) para created_by
        $systemUserId = 1;

        $configs = MonthlyFeeConfig::active()
            ->with(['concept', 'generation'])
            ->when($configId, fn ($q) => $q->where('id', $configId))
            ->get();

        if ($configs->isEmpty()) {
            $this->warn('No hay configuraciones activas de mensualidades.');
            return self::SUCCESS;
        }

        $generated = 0;
        $skipped   = 0;
        $errors    = 0;

        foreach ($configs as $config) {
            // Verificar si el mes/año está dentro del rango de esta config
            $startDate = Carbon::createFromDate($config->start_year, $config->start_month, 1);
            $targetDate = Carbon::createFromDate($year, $month, 1);
            $endDate    = $startDate->copy()->addMonths($config->months_count - 1);

            if ($targetDate->lt($startDate) || $targetDate->gt($endDate)) {
                $this->line("  Config #{$config->id}: fuera de rango, omitiendo.");
                continue;
            }

            // Obtener alumnos inscritos activos de esta generación (filtra por student.generation_id)
            $inscriptions = Inscription::where('status', 'active')
                ->when($config->generation_id, fn ($q) =>
                    $q->whereHas('student', fn ($s) =>
                        $s->where('generation_id', $config->generation_id)
                    )
                )
                ->with('student')
                ->get();

            $this->line("  Config #{$config->id} ({$config->concept->name}): {$inscriptions->count()} alumnos");

            foreach ($inscriptions as $inscription) {
                $student = $inscription->student;

                // ¿Ya existe esta mensualidad?
                $exists = MonthlyFee::where('student_id', $student->id)
                    ->where('monthly_fee_config_id', $config->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("    [DRY] Alumno {$student->id}: {$student->full_name} → \${$config->amount}");
                    $generated++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($config, $student, $inscription, $month, $year, $systemUserId, &$generated) {
                        $periodStart = Carbon::createFromDate($year, $month, 1);
                        $periodEnd   = $periodStart->copy()->endOfMonth();
                        $dueDate     = Carbon::createFromDate($year, $month, $config->generation_day)
                                             ->addDays($config->due_days);

                        // Calcular descuentos automáticos
                        [$discountAmount, $applicableDiscounts] = $this->calculateDiscounts(
                            $student, $config->amount, $config->concept
                        );

                        $subtotal = (float) $config->amount;
                        $total    = max(0, $subtotal - $discountAmount);

                        // Crear PaymentOrder
                        $order = PaymentOrder::create([
                            'student_id'         => $student->id,
                            'payment_concept_id' => $config->payment_concept_id,
                            'chargeable_type'    => MonthlyFee::class,
                            'chargeable_id'      => 0, // se actualiza abajo
                            'period_start'       => $periodStart,
                            'period_end'         => $periodEnd,
                            'subtotal'           => $subtotal,
                            'discount_amount'    => $discountAmount,
                            'tax_amount'         => 0,
                            'total'              => $total,
                            'paid_amount'        => 0,
                            'balance'            => $total,
                            'due_date'           => $dueDate,
                            'status'             => 'pending',
                            'created_by'         => $systemUserId,
                        ]);

                        // Crear MonthlyFee
                        $fee = MonthlyFee::create([
                            'student_id'            => $student->id,
                            'monthly_fee_config_id' => $config->id,
                            'inscription_id'        => $inscription->id,
                            'month'                 => $month,
                            'year'                  => $year,
                            'period_start'          => $periodStart,
                            'period_end'            => $periodEnd,
                            'payment_order_id'      => $order->id,
                            'status'                => 'pending',
                        ]);

                        // Actualizar chargeable_id en el order
                        $order->update(['chargeable_id' => $fee->id]);

                        // Registrar aplicaciones de descuento
                        foreach ($applicableDiscounts as $discount) {
                            DiscountApplication::create([
                                'payment_order_id'    => $order->id,
                                'discount_id'         => $discount['id'],
                                'discount_amount'     => $discount['amount'],
                                'discount_percentage' => $discount['percentage'],
                                'applied_by_type'     => 'automatic',
                                'applied_by'          => null,
                                'applied_at'          => now(),
                                'reason'              => $discount['reason'],
                            ]);

                            // Incrementar used_count
                            Discount::where('id', $discount['id'])->increment('used_count');
                        }

                        $generated++;
                    });
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error("GenerateMonthlyFees error alumno {$student->id}: " . $e->getMessage());
                    $this->error("    Error alumno {$student->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Resultado: {$generated} generadas, {$skipped} existentes, {$errors} errores.");

        return self::SUCCESS;
    }

    /**
     * Calcula los descuentos automáticos aplicables a una mensualidad.
     * Retorna [total_descuento, lista_descuentos_aplicados].
     */
    private function calculateDiscounts(Student $student, float $subtotal, PaymentConcept $concept): array
    {
        $totalDiscount     = 0.0;
        $appliedDiscounts  = [];

        // 1. Descuentos automáticos del catálogo que aplican a mensualidades
        $autoDiscounts = Discount::automatic()
            ->where(fn ($q) => $q->whereNull('applies_to_type')->orWhere('applies_to_type', $concept->type))
            ->get();

        foreach ($autoDiscounts as $discount) {
            if (!$discount->isValid()) continue;

            $amount = $discount->calculateAmount($subtotal);
            $totalDiscount += $amount;

            $appliedDiscounts[] = [
                'id'         => $discount->id,
                'amount'     => $amount,
                'percentage' => $discount->value_type === 'percentage' ? $discount->value : null,
                'reason'     => "Descuento automático: {$discount->name}",
            ];

            if (!$discount->is_stackable) break;
        }

        // 2. Descuentos por referidos activos (aplican a mensualidades)
        if ($concept->type === 'mensualidad') {
            $referrals = Referral::where('referrer_student_id', $student->id)
                ->where('status', 'active')
                ->with(['discount', 'referred.inscriptions'])
                ->get();

            foreach ($referrals as $referral) {
                if (!$referral->isEligible()) continue;

                $discount = $referral->discount;
                if (!$discount || !$discount->isValid()) continue;
                if (!in_array($discount->applies_to_type, [null, 'mensualidad'])) continue;

                $amount = $discount->calculateAmount($subtotal);
                $totalDiscount += $amount;

                $appliedDiscounts[] = [
                    'id'         => $discount->id,
                    'amount'     => $amount,
                    'percentage' => $discount->value_type === 'percentage' ? $discount->value : null,
                    'reason'     => "Descuento referido: alumno #{$referral->referred_student_id}",
                ];
            }
        }

        return [min($totalDiscount, $subtotal), $appliedDiscounts];
    }
}
