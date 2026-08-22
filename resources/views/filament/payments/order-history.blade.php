@php
    /** @var \App\Models\PaymentOrder $order */
    $order->loadMissing([
        'student.activeAgreements',
        'concept',
        'parentOrder',
        'surcharges',
        'orderPayments.payment.method',
        'orderPayments.payment.receivedBy',
        'orderPayments.payment.reference',
    ]);

    $entries = $order->orderPayments
        ->filter(fn ($op) => $op->payment !== null)
        ->sortBy(fn ($op) => $op->payment->payment_date)
        ->values();

    $statusLabels = [
        'pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Liquidado',
        'overdue' => 'Vencido', 'cancelled' => 'Cancelado', 'in_agreement' => 'En convenio',
    ];
    $statusChip = [
        'pending'      => 'text-gray-700 bg-gray-100 ring-gray-300 dark:text-gray-300 dark:bg-gray-800 dark:ring-gray-700',
        'partial'      => 'text-sky-700 bg-sky-50 ring-sky-300 dark:text-sky-300 dark:bg-sky-500/10 dark:ring-sky-500/30',
        'paid'         => 'text-emerald-700 bg-emerald-50 ring-emerald-300 dark:text-emerald-300 dark:bg-emerald-500/10 dark:ring-emerald-500/30',
        'overdue'      => 'text-amber-700 bg-amber-50 ring-amber-300 dark:text-amber-300 dark:bg-amber-500/10 dark:ring-amber-500/30',
        'cancelled'    => 'text-gray-500 bg-gray-100 ring-gray-300 dark:text-gray-500 dark:bg-gray-800 dark:ring-gray-700',
        'in_agreement' => 'text-violet-700 bg-violet-50 ring-violet-300 dark:text-violet-300 dark:bg-violet-500/10 dark:ring-violet-500/30',
    ];

    $total     = (float) $order->total;
    $paid      = (float) $order->paid_amount;
    $balance   = (float) $order->balance;
    $percent   = $order->progress_percent;
    $liquidado = $balance <= 0;
    // Vencimiento efectivo: con convenio vigente, corre por los días extra.
    $dueDate    = $order->effectiveDueDate();
    $extraDays  = $order->agreementExtraDays();
    $agreement  = $extraDays > 0 ? $order->appliedAgreement() : null;
    $atraso     = $order->isOverdue() ? $order->daysLateFor(now()) : 0;

    // El saldo va bajando abono a abono: es el eje de la línea de tiempo.
    $running = 0.0;

    // La marca de vencimiento se coloca UNA vez, en el punto cronológico donde
    // el adeudo entró en mora: antes del primer abono tardío, o al final si el
    // adeudo venció sin que llegara ningún pago después.
    $dueMarkerDone = false;
@endphp

<div class="text-sm text-gray-700 dark:text-gray-300 [font-variant-numeric:tabular-nums]">

    {{-- ═══ Encabezado: el saldo manda ═══════════════════════════════════ --}}
    <header class="pb-5 border-b border-gray-200 dark:border-white/10">

        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
            <div class="min-w-0">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-500">
                    {{ $order->is_surcharge ? 'Recargo por mora' : ($order->concept?->name ?? 'Adeudo') }}
                </p>
                <h2 class="mt-1 text-base font-semibold text-gray-900 dark:text-white truncate">
                    {{ $order->student?->full_name ?? '—' }}
                </h2>
                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-500">
                    {{ $order->folio }}@if($order->student?->student_number) · {{ $order->student->student_number }}@endif
                </p>
            </div>

            <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusChip[$order->status] ?? $statusChip['pending'] }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
        </div>

        {{-- La cifra que la persona vino a ver --}}
        <div class="mt-5">
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-500">
                {{ $liquidado ? 'Saldo' : 'Saldo pendiente' }}
            </p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span @class([
                    'text-4xl font-semibold tracking-tight',
                    'text-emerald-600 dark:text-emerald-400' => $liquidado,
                    'text-gray-900 dark:text-white'          => ! $liquidado,
                ])>${{ number_format($balance, 2) }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-500">
                    de ${{ number_format($total, 2) }} · pagado ${{ number_format($paid, 2) }}
                </span>
            </div>
        </div>

        {{-- Avance: segmentado, para leer la proporción sin comparar píxeles --}}
        <div class="mt-3">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                <div @class([
                        'h-full rounded-full',
                        'bg-emerald-500'                      => $liquidado,
                        'bg-amber-500'                        => ! $liquidado && $atraso > 0,
                        'bg-sky-500'                          => ! $liquidado && $atraso === 0,
                    ])
                    style="width: {{ $percent > 0 ? max(3, $percent) : 0 }}%"></div>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center justify-between gap-x-3 text-xs">
                <span class="text-gray-500 dark:text-gray-500">{{ $percent }}% pagado</span>
                @if($dueDate)
                    <span @class([
                        'font-medium text-amber-600 dark:text-amber-400' => $atraso > 0,
                        'text-gray-500 dark:text-gray-500'               => $atraso === 0,
                    ])>
                        @if($atraso > 0)
                            Venció el {{ $dueDate->format('d/m/Y') }} · {{ $atraso }} {{ $atraso === 1 ? 'día' : 'días' }} de atraso
                        @else
                            Vence el {{ $dueDate->format('d/m/Y') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>

        {{-- Convenio: explica por qué la fecha límite no es la del adeudo --}}
        @if($extraDays > 0)
            <p class="mt-3 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 rounded-lg border-l-2 border-sky-500/60 bg-sky-50/60 dark:bg-sky-500/[0.07] py-2 pl-3 pr-3 text-xs text-gray-600 dark:text-gray-300">
                <span class="font-medium text-sky-700 dark:text-sky-300">Convenio {{ $agreement?->folio }}</span>
                <span>· {{ $extraDays }} días extra:</span>
                <span>vencía el {{ $order->due_date?->format('d/m/Y') }},</span>
                <span class="font-medium text-gray-800 dark:text-gray-100">ahora {{ $dueDate?->format('d/m/Y') }}</span>
            </p>
        @endif
    </header>

    {{-- ═══ Línea de tiempo: el saldo bajando ════════════════════════════ --}}
    <section class="pt-5">
        <div class="flex items-baseline justify-between">
            <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-500">
                Abonos
            </h3>
            @if($entries->isNotEmpty())
                <span class="text-xs text-gray-500 dark:text-gray-500">
                    {{ $entries->count() }} {{ $entries->count() === 1 ? 'movimiento' : 'movimientos' }}
                </span>
            @endif
        </div>

        @if($entries->isEmpty())
            <div class="mt-3 rounded-lg border border-dashed border-gray-300 dark:border-white/15 px-4 py-8 text-center">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Sin abonos registrados</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    @if($atraso > 0)
                        El adeudo venció hace {{ $atraso }} {{ $atraso === 1 ? 'día' : 'días' }}. Usa «Pagar» para registrar el primer abono.
                    @else
                        Usa «Pagar» para registrar el primer abono.
                    @endif
                </p>
            </div>
        @else
            <ol class="mt-4 space-y-0" role="list">

                {{-- Origen: el adeudo completo --}}
                <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-4">
                    <div class="flex flex-col items-center">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-600"></span>
                        <span class="w-px flex-1 bg-gray-300 dark:bg-gray-600"></span>
                    </div>
                    <div class="pb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            Adeudo emitido por <span class="font-medium text-gray-700 dark:text-gray-300">${{ number_format($total, 2) }}</span>
                        </p>
                    </div>
                </li>

                @foreach($entries as $entry)
                    @php
                        $payment  = $entry->payment;
                        $fecha    = $payment->payment_date;
                        $late     = $entry->isLate();
                        $running += (float) $entry->amount_applied;
                        $restante = max(0, $total - $running);

                        $mostrarVencimiento = $dueDate && ! $dueMarkerDone && $fecha && $fecha->gt($dueDate);
                        if ($mostrarVencimiento) { $dueMarkerDone = true; }
                    @endphp

                    {{-- Marca de vencimiento SOBRE el riel: aquí entró en mora --}}
                    @if($mostrarVencimiento)
                        <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-4" aria-label="Fecha de vencimiento">
                            <div class="flex flex-col items-center">
                                <span class="w-px flex-1 border-l border-dashed border-amber-500/70"></span>
                            </div>
                            <div class="flex items-center gap-2 py-2.5">
                                <span class="h-px w-5 bg-amber-500/60"></span>
                                <span class="text-[0.7rem] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                    Venció {{ $dueDate->format('d/m/Y') }}
                                </span>
                            </div>
                        </li>
                    @endif

                    <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-4">
                        {{-- Riel: sólido mientras está al corriente, punteado ámbar en mora --}}
                        <div class="flex flex-col items-center">
                            <span @class([
                                'w-px flex-none h-2.5',
                                'border-l border-dashed border-amber-500/70' => $late,
                                'bg-gray-300 dark:bg-gray-600'               => ! $late,
                            ])></span>
                            <span @class([
                                'h-2.5 w-2.5 shrink-0 rounded-full',
                                'bg-amber-500'   => $late,
                                'bg-emerald-500' => ! $late,
                            ])></span>
                            <span @class([
                                'w-px flex-1',
                                'border-l border-dashed border-amber-500/70' => $late,
                                'bg-gray-300 dark:bg-gray-600'               => ! $late,
                            ])></span>
                        </div>

                        <div class="min-w-0 pb-6">
                            {{-- Renglón principal: monto, fecha, puntualidad --}}
                            <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($entry->amount_applied, 2) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $fecha?->format('d/m/Y') }}
                                </span>
                                @if($late)
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[0.7rem] font-medium text-amber-700 bg-amber-50 ring-1 ring-inset ring-amber-300 dark:text-amber-300 dark:bg-amber-500/10 dark:ring-amber-500/30">
                                        {{ $entry->days_late }} {{ $entry->days_late === 1 ? 'día' : 'días' }} tarde
                                    </span>
                                @elseif(! is_null($entry->days_late))
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[0.7rem] font-medium text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-300 dark:text-emerald-300 dark:bg-emerald-500/10 dark:ring-emerald-500/30">
                                        A tiempo
                                    </span>
                                @endif
                            </div>

                            {{-- Detalle: quién, cómo, con qué comprobante --}}
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $payment->method?->name ?? 'Sin método' }}@if($payment->receivedBy?->name) · Caja: {{ $payment->receivedBy->name }}@endif
                            </p>
                            <p class="mt-0.5 font-mono text-[0.7rem] text-gray-500 dark:text-gray-500">
                                {{ $payment->folio }}@if($payment->receipt_number) · Recibo {{ $payment->receipt_number }}@endif @if($payment->reference?->reference_number) · Ref {{ $payment->reference->reference_number }}@endif
                            </p>

                            @if($payment->notes)
                                <p class="mt-1.5 text-xs italic text-gray-500 dark:text-gray-500">{{ $payment->notes }}</p>
                            @endif

                            {{-- El saldo tras este abono: el eje descendente --}}
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                Saldo tras el abono
                                <span @class([
                                    'ml-1 font-semibold',
                                    'text-emerald-600 dark:text-emerald-400' => $restante <= 0,
                                    'text-gray-700 dark:text-gray-200'       => $restante > 0,
                                ])>${{ number_format($restante, 2) }}</span>
                            </p>
                        </div>
                    </li>
                @endforeach

                {{-- Si venció sin abonos posteriores, la mora va al final del riel --}}
                @if($dueDate && ! $dueMarkerDone && $atraso > 0)
                    <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-4">
                        <div class="flex flex-col items-center">
                            <span class="w-px flex-1 border-l border-dashed border-amber-500/70"></span>
                        </div>
                        <div class="flex items-center gap-2 py-2.5">
                            <span class="h-px w-5 bg-amber-500/60"></span>
                            <span class="text-[0.7rem] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                Venció {{ $dueDate->format('d/m/Y') }}
                            </span>
                        </div>
                    </li>
                @endif

                {{-- Cierre: dónde quedó --}}
                <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-4">
                    <div class="flex flex-col items-center">
                        <span @class([
                            'w-px h-2.5 flex-none',
                            'border-l border-dashed border-amber-500/70' => ! $liquidado && $atraso > 0,
                            'bg-gray-300 dark:bg-gray-600'               => $liquidado || $atraso === 0,
                        ])></span>
                        <span @class([
                            'h-2.5 w-2.5 shrink-0 rounded-full',
                            'bg-emerald-500'                                              => $liquidado,
                            'border-2 border-gray-300 dark:border-gray-600 bg-transparent' => ! $liquidado,
                        ])></span>
                    </div>
                    <div class="pt-px">
                        @if($liquidado)
                            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                Liquidado{{ $order->paid_at ? ' el ' . $order->paid_at->format('d/m/Y') : '' }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Queda por cubrir
                                <span class="ml-1 font-semibold text-gray-900 dark:text-white">${{ number_format($balance, 2) }}</span>
                            </p>
                        @endif
                    </div>
                </li>
            </ol>
        @endif
    </section>

    {{-- ═══ Recargos derivados ═══════════════════════════════════════════ --}}
    @if($order->surcharges->isNotEmpty())
        <section class="mt-5 border-t border-gray-200 dark:border-white/10 pt-5">
            <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-400">
                Recargos que generó
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                Son adeudos aparte: no modifican el saldo de arriba.
            </p>

            <ul class="mt-3 space-y-2">
                @foreach($order->surcharges as $s)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg border-l-2 border-amber-500/60 bg-amber-50/60 dark:bg-amber-500/[0.07] py-2.5 pl-3 pr-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($s->total, 2) }}
                                <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                    {{ rtrim(rtrim(number_format($s->surcharge_rate, 2), '0'), '.') }}% sobre ${{ number_format($total, 2) }}
                                </span>
                            </p>
                            <p class="mt-0.5 font-mono text-[0.7rem] text-gray-500 dark:text-gray-500">
                                {{ $s->folio }}@if($s->due_date) · vence {{ $s->due_date->format('d/m/Y') }}@endif
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[0.7rem] font-medium ring-1 ring-inset {{ $statusChip[$s->status] ?? $statusChip['pending'] }}">
                            {{ $statusLabels[$s->status] ?? $s->status }}@if($s->paid_amount > 0 && $s->balance > 0) · resta ${{ number_format($s->balance, 2) }}@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Si este adeudo ES un recargo, se enlaza al que lo originó --}}
    @if($order->is_surcharge && $order->parentOrder)
        <p class="mt-5 border-t border-gray-200 dark:border-white/10 pt-4 text-xs text-gray-500 dark:text-gray-500">
            Interés por mora derivado de
            <span class="font-mono text-gray-700 dark:text-gray-300">{{ $order->parentOrder->folio }}</span>
            ({{ $order->parentOrder->concept?->name }}, total ${{ number_format($order->parentOrder->total, 2) }}).
        </p>
    @endif

    @if($order->notes)
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
            <span class="font-medium text-gray-600 dark:text-gray-400">Notas:</span> {{ $order->notes }}
        </p>
    @endif
</div>
