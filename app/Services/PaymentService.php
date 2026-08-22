<?php

namespace App\Services;

use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\PaymentConcept;
use App\Models\PaymentMethod;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPayment;
use App\Models\PaymentReference;
use App\Models\Setting;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Get;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PaymentService
{
    /** Código del concepto usado para los adeudos de interés por mora. */
    public const SURCHARGE_CONCEPT_CODE = 'RECARGO';

    public static function defaultSurchargeRate(): float
    {
        return (float) Setting::get('default_surcharge_rate', 10);
    }

    public static function surchargeDueDays(): int
    {
        return (int) Setting::get('surcharge_due_days', 15);
    }

    /**
     * Concepto de pago para recargos. Se crea al vuelo la primera vez para no
     * depender de un seeder que quizá no corrió en el servidor.
     */
    public static function surchargeConcept(): PaymentConcept
    {
        return PaymentConcept::firstOrCreate(
            ['code' => self::SURCHARGE_CONCEPT_CODE],
            [
                'name'           => 'Recargo por pago tardío',
                'description'    => 'Interés generado automáticamente cuando un adeudo se paga después de su fecha de vencimiento.',
                'type'           => 'otro',
                'default_amount' => 0,
                'is_periodic'    => false,
                'is_taxable'     => false,
                'tax_rate'       => 0,
                'active'         => true,
            ],
        );
    }

    /**
     * Recargo ya generado para este adeudo (solo se permite uno).
     */
    public static function existingSurcharge(PaymentOrder $order): ?PaymentOrder
    {
        return $order->surcharges()->where('status', '!=', 'cancelled')->first();
    }

    /**
     * Monto del interés: % sobre el TOTAL del adeudo.
     */
    public static function calculateSurchargeAmount(PaymentOrder $order, float $rate): float
    {
        return round(((float) $order->total) * ($rate / 100), 2);
    }

    public static function paymentFormSchema(PaymentOrder $order): array
    {
        // Fecha límite real: si el alumno tiene convenio vigente, la del adeudo
        // corrida por los días extra que concede.
        $dueDate           = $order->effectiveDueDate();
        $agreement         = $order->appliedAgreement();
        $extraDays         = $order->agreementExtraDays();
        $existingSurcharge = static::existingSurcharge($order);

        // ¿La fecha capturada en el formulario cae después del vencimiento?
        $isLate = function (Get $get) use ($order): bool {
            $date = $get('payment_date');

            return $date ? $order->daysLateFor($date) > 0 : false;
        };

        return [
            Forms\Components\Placeholder::make('order_info')
                ->label('Adeudo')
                ->content(sprintf(
                    '%s — Total: $%s — Pagado: $%s — Saldo: $%s%s',
                    $order->concept?->name ?? $order->folio,
                    number_format($order->total, 2),
                    number_format($order->paid_amount, 2),
                    number_format($order->balance, 2),
                    $dueDate ? ' — Vence: ' . $dueDate->format('d/m/Y') : '',
                )),

            // Aviso de convenio: explica por qué la fecha límite no es la del adeudo.
            Forms\Components\Placeholder::make('agreement_notice')
                ->hiddenLabel()
                ->columnSpanFull()
                ->visible(fn () => $extraDays > 0)
                ->content(fn (): HtmlString => new HtmlString(
                    '<div class="rounded-lg border border-sky-300 bg-sky-50 p-3 text-sm text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">'
                    . '<p class="font-semibold">Este alumno tiene convenio ' . e($agreement?->folio ?? '') . '.</p>'
                    . '<p class="mt-0.5">Se le conceden <strong>' . $extraDays . ' días extra</strong>, así que la fecha límite '
                    . 'pasa del ' . $order->due_date?->format('d/m/Y') . ' al <strong>' . $dueDate?->format('d/m/Y') . '</strong>.</p></div>'
                )),

            // La fecha va PRIMERO: es la que determina si el pago llegó tarde.
            Forms\Components\DatePicker::make('payment_date')
                ->label('Fecha de pago')
                ->required()
                ->default(now())
                ->live()
                ->helperText($dueDate
                    ? ($extraDays > 0
                        ? 'Fecha límite con convenio: ' . $dueDate->format('d/m/Y')
                        : 'Fecha de vencimiento del adeudo: ' . $dueDate->format('d/m/Y'))
                    : null),

            // Alerta amarilla de mora.
            Forms\Components\Placeholder::make('late_warning')
                ->hiddenLabel()
                ->columnSpanFull()
                ->visible($isLate)
                ->content(function (Get $get) use ($order, $dueDate): HtmlString {
                    $days = $order->daysLateFor($get('payment_date'));

                    return new HtmlString(
                        '<div class="flex gap-3 rounded-lg border border-warning-400/40 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">'
                        . '<span class="text-lg leading-none">⚠️</span>'
                        . '<div><p class="font-semibold">Este pago ya generó recargos.</p>'
                        . '<p class="mt-0.5">Venció hace <strong>' . $days . ' día' . ($days === 1 ? '' : 's') . '</strong>'
                        . ($dueDate ? ' (' . $dueDate->format('d/m/Y') . ')' : '') . '.</p></div></div>'
                    );
                }),

            Forms\Components\Select::make('payment_method_id')
                ->label('Método de pago')
                ->options(PaymentMethod::query()->active()->pluck('name', 'id'))
                ->required()
                ->live(),

            Forms\Components\TextInput::make('amount_applied')
                ->label('Monto a pagar')
                ->numeric()
                ->prefix('$')
                ->required()
                ->minValue(0.01)
                ->maxValue($order->balance)
                ->helperText('Saldo pendiente: $' . number_format($order->balance, 2) . '. Puede ser un pago parcial (anticipo).'),

            // Si ya hay un recargo, se informa en vez de ofrecer generarlo otra vez.
            Forms\Components\Placeholder::make('surcharge_exists')
                ->hiddenLabel()
                ->columnSpanFull()
                ->visible(fn (Get $get) => $existingSurcharge !== null && $isLate($get))
                ->content(new HtmlString(
                    '<div class="rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">'
                    . 'Este adeudo ya tiene un recargo generado: <strong>' . ($existingSurcharge?->folio ?? '') . '</strong> por $'
                    . number_format($existingSurcharge?->total ?? 0, 2) . '. No se generará otro.</div>'
                )),

            Forms\Components\Checkbox::make('generate_surcharge')
                ->label('Pago con interés')
                ->helperText('Marca esta casilla para cobrar un interés por mora sobre este adeudo.')
                ->live()
                ->visible(fn (Get $get) => $existingSurcharge === null && $isLate($get)),

            Forms\Components\Section::make('Interés por mora')
                ->icon('heroicon-o-exclamation-triangle')
                ->visible(fn (Get $get) => $existingSurcharge === null && $isLate($get) && $get('generate_surcharge'))
                ->schema([
                    Forms\Components\TextInput::make('surcharge_rate')
                        ->label('% de interés a calcular')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0.01)
                        ->maxValue(100)
                        ->required(fn (Get $get) => (bool) $get('generate_surcharge'))
                        ->default(static::defaultSurchargeRate())
                        ->live(debounce: 500),

                    Forms\Components\Placeholder::make('surcharge_preview')
                        ->label('Monto del interés')
                        ->content(function (Get $get) use ($order): string {
                            $rate = (float) ($get('surcharge_rate') ?? 0);

                            return '$' . number_format(static::calculateSurchargeAmount($order, $rate), 2)
                                . ' (' . rtrim(rtrim(number_format($rate, 2), '0'), '.') . '% sobre $'
                                . number_format($order->total, 2) . ')';
                        }),

                    Forms\Components\Placeholder::make('surcharge_legend')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->content(new HtmlString(
                            '<p class="text-sm text-gray-600 dark:text-gray-400">El interés se generará automáticamente '
                            . 'como un <strong>adeudo nuevo</strong> a nombre del alumno, y podrás cobrarlo en la siguiente ventana.</p>'
                        )),
                ])
                ->columns(2),

            Forms\Components\TextInput::make('receipt_number')
                ->label('No. de recibo')
                ->maxLength(255),

            Forms\Components\Fieldset::make('Referencia bancaria')
                ->schema([
                    Forms\Components\TextInput::make('reference_number')
                        ->label('Referencia / folio bancario'),
                    Forms\Components\TextInput::make('bank')
                        ->label('Banco'),
                ])
                ->visible(fn (Get $get) => (bool) optional(PaymentMethod::find($get('payment_method_id')))->requires_reference),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(2),
        ];
    }

    /**
     * Registra un abono sobre un adeudo y, opcionalmente, genera el adeudo de
     * interés por mora.
     *
     * @return array{payment: Payment, surcharge: ?PaymentOrder}
     */
    public static function registerPayment(PaymentOrder $order, array $data): array
    {
        $amount = round((float) $data['amount_applied'], 2);

        if ($amount <= 0 || $amount > $order->balance + 0.01) {
            throw new \InvalidArgumentException('El monto debe ser mayor a 0 y no exceder el saldo pendiente.');
        }

        return DB::transaction(function () use ($order, $data, $amount) {
            $paymentDate = $data['payment_date'];
            $daysLate    = $order->daysLateFor($paymentDate);

            $payment = Payment::create([
                'student_id'        => $order->student_id,
                'payment_method_id' => $data['payment_method_id'],
                'amount_received'   => $amount,
                'amount_applied'    => $amount,
                'change_amount'     => 0,
                'payment_date'      => $paymentDate,
                'receipt_number'    => $data['receipt_number'] ?? null,
                'status'            => 'applied',
                'notes'             => $data['notes'] ?? null,
                'received_by'       => auth()->id(),
                'created_by'        => auth()->id(),
            ]);

            PaymentOrderPayment::create([
                'payment_id'       => $payment->id,
                'payment_order_id' => $order->id,
                'amount_applied'   => $amount,
                'days_late'        => $daysLate,
            ]);

            if (! empty($data['reference_number'])) {
                PaymentReference::create([
                    'payment_id'       => $payment->id,
                    'reference_number' => $data['reference_number'],
                    'bank'             => $data['bank'] ?? null,
                ]);
            }

            $surcharge = null;

            if (! empty($data['generate_surcharge']) && $daysLate > 0) {
                $surcharge = static::createSurchargeOrder(
                    $order,
                    (float) ($data['surcharge_rate'] ?? static::defaultSurchargeRate()),
                    $paymentDate,
                    $daysLate,
                );
            }

            $order->applyPayment($amount);

            if ($order->isFullyPaid() && $order->chargeable_type === MonthlyFee::class) {
                MonthlyFee::where('id', $order->chargeable_id)->update(['status' => 'paid']);
            }

            return ['payment' => $payment, 'surcharge' => $surcharge];
        });
    }

    /**
     * Notificación estándar tras registrar un abono. Si se generó un recargo,
     * lo anuncia con su folio para que caja sepa que hay un adeudo nuevo.
     *
     * @param  array{payment: Payment, surcharge: ?PaymentOrder}  $result
     */
    public static function notifyPaymentRegistered(array $result): void
    {
        $surcharge = $result['surcharge'] ?? null;

        if ($surcharge) {
            \Filament\Notifications\Notification::make()
                ->title('Pago registrado y recargo generado')
                ->body(sprintf(
                    'Se creó el adeudo de interés %s por $%s. Podrás cobrarlo desde Adeudos.',
                    $surcharge->folio,
                    number_format($surcharge->total, 2),
                ))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        \Filament\Notifications\Notification::make()
            ->title('Pago registrado correctamente')
            ->success()
            ->send();
    }

    /**
     * Crea el adeudo de interés derivado de un adeudo pagado con retraso.
     * Devuelve null si ya existía uno (no se duplica).
     */
    public static function createSurchargeOrder(
        PaymentOrder $order,
        float $rate,
        mixed $paymentDate,
        int $daysLate,
    ): ?PaymentOrder {
        if (static::existingSurcharge($order)) {
            return null;
        }

        $amount = static::calculateSurchargeAmount($order, $rate);

        if ($amount <= 0) {
            return null;
        }

        $paidOn = $paymentDate instanceof \DateTimeInterface
            ? Carbon::instance($paymentDate)
            : Carbon::parse($paymentDate);

        return PaymentOrder::create([
            'student_id'              => $order->student_id,
            'payment_concept_id'      => static::surchargeConcept()->id,
            'parent_payment_order_id' => $order->id,
            'is_surcharge'            => true,
            'surcharge_rate'          => $rate,
            'subtotal'                => $amount,
            'discount_amount'         => 0,
            'tax_amount'              => 0,
            'total'                   => $amount,
            'paid_amount'             => 0,
            'balance'                 => $amount,
            'due_date'                => $paidOn->copy()->addDays(static::surchargeDueDays())->toDateString(),
            'status'                  => 'pending',
            'notes'                   => sprintf(
                'Interés del %s%% sobre el adeudo %s (total $%s), pagado con %d día(s) de retraso.',
                rtrim(rtrim(number_format($rate, 2), '0'), '.'),
                $order->folio,
                number_format($order->total, 2),
                $daysLate,
            ),
            'created_by'              => auth()->id() ?? $order->created_by,
        ]);
    }
}
