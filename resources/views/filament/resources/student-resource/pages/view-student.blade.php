<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="md:col-span-2 space-y-6">

            <div class="rounded-lg p-4 flex items-center justify-between shadow-sm mb-6 bg-gray-100 dark:bg-gray-800">
                <div class="flex items-center space-x-4">

                    <div>
                        <h1 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $record->fullname }}</h1>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>📄 Matrícula: {{ $record->student_number }}</span>
                            @if (strtolower($record->status) === 'activo' || strtolower($record->status) === 'active')
                                <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Activo</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">{{ ucfirst($record->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('filament.admin.resources.students.edit', $record) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-sm px-4 py-2 rounded flex items-center text-white space-x-1">
                    <x-heroicon-o-pencil class="w-4 h-4" />
                    <span>Editar</span>
                </a>
            </div>

            {{-- Datos Personales --}}
            <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Datos Personales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-400">
                    <p><strong>CURP:</strong> {{ $record->curp ?? 'N/A' }}</p>
                    <p><strong>Correo electrónico:</strong> {{ $record->email ?? 'N/A' }}</p>
                    <p><strong>Teléfono:</strong> {{ $record->phone ?? 'N/A' }}</p>
                    <p><strong>Fecha de nacimiento:</strong> {{ $record->date_of_birth?->format('d/m/Y') ?? 'N/A' }}</p>
                    <p class="md:col-span-2"><strong>Dirección:</strong>
                        {{ $record->street }}, {{ $record->city }}, {{ $record->state }},
                        CP {{ $record->postal_code }}, {{ $record->country }}
                    </p>
                </div>
            </div>
            <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Información Académica</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700 dark:text-gray-400">

                    <p><strong>Grupo:</strong> {{ $record->lastInscription?->group?->code ?? 'N/A' }}</p>
                    <p><strong>Última sesión:</strong> No registrada</p>
                    <p><strong>Modalidad:</strong> {{ $record->lastInscription?->group?->period->career?->modality?->name ?? 'N/A' }}</p>
                </div>
            </div>
            {{-- Pagos: Tabs --}}
            @php
                $paymentOrders = $record->paymentOrders()->with('concept')->orderBy('due_date')->get();
                $monthlyFees   = $record->monthlyFees()->with(['config.concept', 'paymentOrder'])->orderByDesc('year')->orderByDesc('month')->get();
                $payments      = $record->payments()->with('method')->orderByDesc('payment_date')->get();
                $monthNames    = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
                $statusColors  = ['pending'=>'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200','partial'=>'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200','paid'=>'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200','overdue'=>'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200','cancelled'=>'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300','in_agreement'=>'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-200'];
                $statusLabels  = ['pending'=>'Pendiente','partial'=>'Parcial','paid'=>'Pagado','overdue'=>'Vencido','cancelled'=>'Cancelado','in_agreement'=>'En convenio'];
                $pendingCount  = $paymentOrders->whereIn('status', ['pending','partial','overdue'])->count();
            @endphp

            <div class="rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                 x-data="{ tab: 'adeudos' }">

                {{-- Tab bar --}}
                <div class="flex bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 pt-3 gap-1">

                    <button @click="tab = 'adeudos'"
                            :class="tab === 'adeudos'
                                ? 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-900 text-primary-600 dark:text-primary-400 font-semibold'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-900/40'"
                            class="relative flex items-center gap-2 px-4 py-2.5 rounded-t-lg text-sm transition-all focus:outline-none -mb-px border border-transparent">
                        <x-heroicon-o-document-currency-dollar class="w-4 h-4 flex-shrink-0" />
                        Adeudos
                        <span :class="tab === 'adeudos' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-bold transition-colors">
                            {{ $paymentOrders->count() }}
                        </span>
                        @if($pendingCount > 0)
                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white dark:border-gray-900"></span>
                        @endif
                    </button>

                    <button @click="tab = 'mensualidades'"
                            :class="tab === 'mensualidades'
                                ? 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-900 text-primary-600 dark:text-primary-400 font-semibold'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-900/40'"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-t-lg text-sm transition-all focus:outline-none -mb-px border border-transparent">
                        <x-heroicon-o-calendar-days class="w-4 h-4 flex-shrink-0" />
                        Mensualidades
                        <span :class="tab === 'mensualidades' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-bold transition-colors">
                            {{ $monthlyFees->count() }}
                        </span>
                    </button>

                    <button @click="tab = 'pagos'"
                            :class="tab === 'pagos'
                                ? 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-900 text-primary-600 dark:text-primary-400 font-semibold'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-900/40'"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-t-lg text-sm transition-all focus:outline-none -mb-px border border-transparent">
                        <x-heroicon-o-banknotes class="w-4 h-4 flex-shrink-0" />
                        Historial de Pagos
                        <span :class="tab === 'pagos' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-bold transition-colors">
                            {{ $payments->count() }}
                        </span>
                    </button>

                </div>

                {{-- Contenido de tabs --}}
                <div class="p-6 bg-gray-50 dark:bg-gray-800">

                    {{-- Tab 1: Adeudos --}}
                    <div x-show="tab === 'adeudos'" x-cloak>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Adeudos del alumno</h3>
                            <a href="{{ \App\Filament\Resources\PaymentOrderResource::getUrl('create') }}?student_id={{ $record->id }}"
                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium rounded-lg transition-colors">
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Agregar adeudo
                            </a>
                        </div>
                        @if($paymentOrders->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-100 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Folio</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Concepto</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pagado</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vence</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($paymentOrders as $order)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $order->folio ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $order->concept->name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($order->total, 2) }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($order->paid_amount, 2) }}</td>
                                                <td class="px-4 py-3 text-right font-semibold {{ $order->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                    ${{ number_format($order->balance, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $order->due_date?->format('d/m/Y') ?? '—' }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                        {{ $statusLabels[$order->status] ?? $order->status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-document-currency-dollar class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                <p class="text-sm">No hay adeudos registrados.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tab 2: Mensualidades --}}
                    <div x-show="tab === 'mensualidades'" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Mensualidades generadas</h3>
                        </div>
                        @if($monthlyFees->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-100 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Período</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Concepto</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pagado</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vence</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($monthlyFees as $fee)
                                            @php
                                                $feeStatus = $fee->status;
                                                $feeColor  = ['pending'=>'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200','paid'=>'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200','cancelled'=>'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'][$feeStatus] ?? 'bg-gray-100 text-gray-800';
                                                $feeLabel  = ['pending'=>'Pendiente','paid'=>'Pagado','cancelled'=>'Cancelado'][$feeStatus] ?? $feeStatus;
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ ($monthNames[$fee->month] ?? $fee->month) . ' ' . $fee->year }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $fee->config?->concept?->name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($fee->paymentOrder?->total ?? 0, 2) }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($fee->paymentOrder?->paid_amount ?? 0, 2) }}</td>
                                                <td class="px-4 py-3 text-right font-semibold {{ ($fee->paymentOrder?->balance ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                    ${{ number_format($fee->paymentOrder?->balance ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $fee->paymentOrder?->due_date?->format('d/m/Y') ?? '—' }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $feeColor }}">
                                                        {{ $feeLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-calendar-days class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                <p class="text-sm">No hay mensualidades registradas.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Tab 3: Historial de Pagos --}}
                    <div x-show="tab === 'pagos'" x-cloak>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Historial de pagos recibidos</h3>
                        </div>
                        @if($payments->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-100 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Folio</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Método</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Recibido</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aplicado</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fecha</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estado</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cajero</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($payments as $payment)
                                            @php
                                                $pyColor = ['applied'=>'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200','partial'=>'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200','pending'=>'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300','cancelled'=>'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'][$payment->status] ?? 'bg-gray-100 text-gray-800';
                                                $pyLabel = ['applied'=>'Aplicado','partial'=>'Parcial','pending'=>'Pendiente','cancelled'=>'Cancelado'][$payment->status] ?? $payment->status;
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $payment->folio ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $payment->method?->name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($payment->amount_received, 2) }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format($payment->amount_applied, 2) }}</td>
                                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 text-xs">
                                                    {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y H:i') : '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pyColor }}">
                                                        {{ $pyLabel }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ $payment->receivedBy?->name ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-banknotes class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                <p class="text-sm">No hay pagos registrados.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            @if($record->lastInscription)
                {{-- Asignaturas y Calificaciones por Unidades --}}
                <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Asignaturas y Calificaciones</h2>
                        <a href="{{ route('student.download-report-card', ['studentId' => $record->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Descargar Boleta
                        </a>
                    </div>

                    @php
                        $assignments = $record->lastInscription->group->assignments()
                            ->with(['subject', 'teacher', 'units.qualification' => function($query) use ($record) {
                                $query->where('student_id', $record->id);
                            }])
                            ->get();
                    @endphp

                    @if($assignments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Materia
                                        </th>
                                        @php
                                            $maxUnits = $assignments->max(function($assignment) {
                                                return $assignment->units->count();
                                            });
                                        @endphp
                                        @for($i = 1; $i <= $maxUnits; $i++)
                                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Unidad {{ $i }}
                                            </th>
                                        @endfor
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Promedio
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Calificación Final
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($assignments as $assignment)
                                        @php
                                            $units = $assignment->units->sortBy('id');
                                            $scores = $units->pluck('qualification.score')->filter(function($score) {
                                                return is_numeric($score) && $score !== '-';
                                            });
                                            $average = $scores->isNotEmpty() ? round($scores->avg(), 1) : '-';
                                        @endphp
                                        <tr class="">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    <a href="{{ \App\Filament\Resources\AssignmentResource::getUrl('view', ['record' => $assignment->id]) }}"
                                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors duration-200">
                                                    {{ $assignment->subject->name }}
                                                    </a>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">

                                                    {{ $assignment->teacher->fullName() }}
                                                </div>
                                            </td>
                                            @for($i = 1; $i <= $maxUnits; $i++)
                                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                                    @php
                                                        $unit = $units->get($i - 1);
                                                        $score = $unit ? $unit->qualification->score : '-';
                                                    @endphp
                                                    @if($unit)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            @if(is_numeric($score) && $score >= 7)
                                                                bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200
                                                            @elseif(is_numeric($score) && $score >= 6)
                                                                bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200
                                                            @elseif(is_numeric($score) && $score < 6)
                                                                bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200
                                                            @else
                                                                bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                            @endif">
                                                            {{ $score }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            @endfor
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                                    @if(is_numeric($average) && $average >= 7)
                                                        bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200
                                                    @elseif(is_numeric($average) && $average >= 6)
                                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200
                                                    @elseif(is_numeric($average) && $average < 6)
                                                        bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200
                                                    @else
                                                        bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                    @endif">
                                                    {{ $average }}
                                                </span>
                                            </td>
                                            {{-- Calificación Final --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @php
                                                    // Obtener la última calificación final para este assignment
                                                    $finalGrade = \App\Models\FinalGrade::getLatestGrade($record->id, $assignment->id);
                                                @endphp

                                                @if($finalGrade)
                                                    <div class="flex flex-col items-center space-y-1">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                                            @if($finalGrade->isFailed())
                                                                bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200
                                                            @else
                                                                bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200
                                                            @endif">
                                                            {{ $finalGrade->grade }}
                                                        </span>

                                                        @if($finalGrade->attempt_type)
                                                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200 px-2 py-0.5 rounded">
                                                                {{ $finalGrade->attempt_type }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 text-xs">Pendiente</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    {{-- Fila del promedio general mejorado --}}
                                    @php
                                        // Calcular promedios por unidad (de todas las materias)
                                        $unitAverages = [];
                                        for ($unitIndex = 1; $unitIndex <= $maxUnits; $unitIndex++) {
                                            $unitScores = [];
                                            foreach($assignments as $assignment) {
                                                $units = $assignment->units->sortBy('id');
                                                $unit = $units->get($unitIndex - 1);
                                                if ($unit && is_numeric($unit->qualification->score)) {
                                                    $unitScores[] = $unit->qualification->score;
                                                }
                                            }
                                            $unitAverages[$unitIndex] = count($unitScores) > 0 ? round(array_sum($unitScores) / count($unitScores), 1) : '-';
                                        }

                                        // Calcular promedio general basado en materias con calificación final
                                        $finalGradesAverages = [];
                                        $assignmentsWithFinalGrades = 0;
                                        foreach($assignments as $assignment) {
                                            $finalGrade = \App\Models\FinalGrade::getLatestGrade($record->id, $assignment->id);
                                            if ($finalGrade) {
                                                $finalGradesAverages[] = $finalGrade->grade;
                                                $assignmentsWithFinalGrades++;
                                            }
                                        }

                                        $generalAverageFromFinals = count($finalGradesAverages) > 0 ? round(array_sum($finalGradesAverages) / count($finalGradesAverages), 1) : '-';

                                        // Promedio tradicional de parciales para comparación
                                        $allPartialAverages = [];
                                        foreach($assignments as $assignment) {
                                            $units = $assignment->units->sortBy('id');
                                            $scores = $units->pluck('qualification.score')->filter(function($score) {
                                                return is_numeric($score) && $score !== '-';
                                            });
                                            if ($scores->isNotEmpty()) {
                                                $average = round($scores->avg(), 1);
                                                $allPartialAverages[] = $average;
                                            }
                                        }
                                        $generalPartialAverage = count($allPartialAverages) > 0 ? round(array_sum($allPartialAverages) / count($allPartialAverages), 1) : '-';
                                    @endphp

                                    {{-- Fila de promedios por unidad (opaco/secundario) --}}
                                    <tr class="bg-gray-50 dark:bg-gray-700 opacity-50 border-t border-gray-200 dark:border-gray-600">
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 italic">
                                                Promedio por Unidad
                                            </div>
                                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                                (Todas las materias)
                                            </div>
                                        </td>
                                        @for($i = 1; $i <= $maxUnits; $i++)
                                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                                @php $unitAvg = $unitAverages[$i]; @endphp
                                                @if($unitAvg !== '-')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border-2
                                                        @if($unitAvg >= 7)
                                                            bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700
                                                        @elseif($unitAvg >= 6)
                                                            bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700
                                                        @else
                                                            bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700
                                                        @endif">
                                                        {{ $unitAvg }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300 dark:text-gray-600 text-xs">-</span>
                                                @endif
                                            </td>
                                        @endfor
                                        <td class="px-6 py-3 whitespace-nowrap text-center">
                                            @if($generalPartialAverage !== '-')
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border-2
                                                    @if($generalPartialAverage >= 7)
                                                        bg-green-50 text-green-800 border-green-300 dark:bg-green-900/20 dark:text-green-300 dark:border-green-600
                                                    @elseif($generalPartialAverage >= 6)
                                                        bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-600
                                                    @else
                                                        bg-red-50 text-red-800 border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-600
                                                    @endif">
                                                    {{ $generalPartialAverage }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600 text-xs">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-center">
                                            @if($generalAverageFromFinals !== '-')
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border-2
                                                    @if($generalAverageFromFinals >= 7)
                                                        bg-green-50 text-green-800 border-green-300 dark:bg-green-900/20 dark:text-green-300 dark:border-green-600
                                                    @elseif($generalAverageFromFinals >= 6)
                                                        bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-600
                                                    @else
                                                        bg-red-50 text-red-800 border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-600
                                                    @endif">
                                                    {{ $generalAverageFromFinals }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600 text-xs italic">-</span>
                                            @endif
                                        </td>
                                    </tr>


                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">No hay asignaturas asignadas para este grupo.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">

            {{-- Foto del alumno --}}
            <div class="rounded-lg overflow-hidden shadow-sm bg-gray-100 dark:bg-gray-800">
                @if($record->photo)
                    <div class="relative">
                        <img src="{{ Storage::url($record->photo) }}"
                             alt="{{ $record->fullname }}"
                             class="w-full aspect-square object-cover" />
                        {{-- Overlay con botones --}}
                        <div class="absolute inset-0 bg-black/0 hover:bg-black/40 transition-colors duration-200 flex items-end justify-center pb-4 gap-2 opacity-0 hover:opacity-100">
                            <a href="{{ Storage::url($record->photo) }}"
                               download="{{ $record->student_number }}_foto.jpg"
                               class="inline-flex items-center gap-1 bg-white/90 hover:bg-white text-gray-800 text-xs font-medium px-3 py-1.5 rounded-lg shadow transition-colors">
                                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                Descargar
                            </a>
                        </div>
                    </div>
                @else
                    <div class="aspect-square flex flex-col items-center justify-center bg-gray-200 dark:bg-gray-700">
                        <x-heroicon-o-user class="w-20 h-20 text-gray-400 dark:text-gray-500 mb-2" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sin foto</p>
                    </div>
                @endif

                <div class="p-4 flex items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Foto del alumno</p>
                        @if($record->photo)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pasa el cursor sobre la foto para descargar</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($record->photo)
                            <a href="{{ Storage::url($record->photo) }}"
                               download="{{ $record->student_number }}_foto.jpg"
                               class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-white-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                Descargar
                            </a>
                        @endif
                        <button wire:click="mountAction('uploadPhoto')" type="button"
                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors">
                            <x-heroicon-o-camera class="w-3.5 h-3.5" />
                            {{ $record->photo ? 'Cambiar' : 'Subir foto' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Inscripción</h2>
                @if($record->lastInscription)
                <div class="p-3 mb-2 rounded border border-gray-200 dark:border-gray-700
                                    bg-white dark:bg-gray-900">
                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $record->lastInscription->group->code }} <small > - ({{ $record->lastInscription->status }})</small>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $record->lastInscription->group->period->career->name }}
                        </p>
                    </div>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">No hay inscripciones disponibles.</p>
                @endif
            </div>


            <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Documentos</h2>
                @forelse ($record->documents as $document)
                    <div class="p-3 mb-2 rounded border border-gray-200 dark:border-gray-700
                                    flex justify-between items-center bg-white dark:bg-gray-900">
                        <span class="text-gray-800 dark:text-gray-100">{{ $document->name ?? 'Documento' }}</span>
                        <div class="space-x-2">
                            <a href="{{ asset('storage/' . $document->src) }}" target="_blank"
                                class="text-blue-600 hover:underline dark:text-blue-400">Ver</a>
                            <a href="{{ asset('storage/' . $document->src) }}" download="{{ basename($document->src) }}"
                                class="text-green-600 hover:underline dark:text-green-400">Descargar</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">No hay documentos disponibles.</p>
                @endforelse
            </div>



        </div>
    </div>

    <x-filament::button href="{{ route('filament.admin.resources.students.index') }}" class="mt-6">
        ← Volver
    </x-filament::button>
</x-filament::page>
