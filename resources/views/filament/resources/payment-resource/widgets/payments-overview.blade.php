@php
    $stats = $this->getStats();
@endphp

<x-filament-widgets::widget>
    <div class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 space-y-5">

        {{-- Selector de periodo --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-800 dark:text-gray-100">Balance de pagos</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Periodo: {{ $stats['from']->format('d/m/Y') }} — {{ $stats['to']->format('d/m/Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Periodo</label>
                    <select wire:model.live="period"
                            class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
                        <option value="today">Hoy</option>
                        <option value="this_week">Esta semana</option>
                        <option value="this_month">Este mes</option>
                        <option value="last_month">Mes anterior</option>
                        <option value="this_year">Este año</option>
                        <option value="custom">Rango personalizado</option>
                    </select>
                </div>

                @if($period === 'custom')
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Desde</label>
                        <input type="date" wire:model.live="dateFrom"
                               class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
                        <input type="date" wire:model.live="dateUntil"
                               class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="rounded-lg p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-2 text-green-700 dark:text-green-300 text-xs font-medium uppercase">
                    <x-heroicon-o-banknotes class="w-4 h-4" />
                    Cobrado en el periodo
                </div>
                <p class="mt-1 text-2xl font-bold text-green-800 dark:text-green-200">${{ number_format($stats['total_cobrado'], 2) }}</p>
                <p class="text-xs text-green-700/80 dark:text-green-300/70 mt-0.5">
                    {{ $stats['count_pagos'] }} pago(s) · {{ $stats['count_completos'] }} completos · {{ $stats['count_parciales'] }} abonos
                    @if($stats['count_anticipos'] > 0)
                        · {{ $stats['count_anticipos'] }} anticipos
                    @endif
                </p>
            </div>

            <div class="rounded-lg p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 text-xs font-medium uppercase">
                    <x-heroicon-o-document-currency-dollar class="w-4 h-4" />
                    Saldo pendiente (global)
                </div>
                <p class="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-200">${{ number_format($stats['total_pendiente'], 2) }}</p>
                <p class="text-xs text-blue-700/80 dark:text-blue-300/70 mt-0.5">Adeudos abiertos a la fecha</p>
            </div>

            <div class="rounded-lg p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-300 text-xs font-medium uppercase">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    Vencido (global)
                </div>
                <p class="mt-1 text-2xl font-bold text-red-800 dark:text-red-200">${{ number_format($stats['total_vencido'], 2) }}</p>
                <p class="text-xs text-red-700/80 dark:text-red-300/70 mt-0.5">Adeudos con fecha de vencimiento pasada</p>
            </div>

            <div class="rounded-lg p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300 text-xs font-medium uppercase">
                    <x-heroicon-o-credit-card class="w-4 h-4" />
                    Recibido en el periodo
                </div>
                <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-gray-100">${{ number_format($stats['total_recibido'], 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Incluye cambio entregado a alumnos</p>
            </div>
        </div>

        {{-- Recargos por mora --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300 text-xs font-medium uppercase">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    Recargos generados
                </div>
                <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">${{ number_format($stats['surcharge_generado'], 2) }}</p>
                <p class="text-xs text-amber-700/80 dark:text-amber-300/70 mt-0.5">
                    {{ $stats['surcharge_count'] }} adeudo(s) de interés en el periodo
                </p>
            </div>

            <div class="rounded-lg p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300 text-xs font-medium uppercase">
                    <x-heroicon-o-check-circle class="w-4 h-4" />
                    Recargos cobrados
                </div>
                <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">${{ number_format($stats['surcharge_cobrado'], 2) }}</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-300/70 mt-0.5">De los recargos generados en el periodo</p>
            </div>

            <div class="rounded-lg p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <div class="flex items-center gap-2 text-orange-700 dark:text-orange-300 text-xs font-medium uppercase">
                    <x-heroicon-o-clock class="w-4 h-4" />
                    Recargos por cobrar (global)
                </div>
                <p class="mt-1 text-2xl font-bold text-orange-800 dark:text-orange-200">${{ number_format($stats['surcharge_pendiente'], 2) }}</p>
                <p class="text-xs text-orange-700/80 dark:text-orange-300/70 mt-0.5">Saldo abierto de intereses a la fecha</p>
            </div>
        </div>

        {{-- Desglose por método de pago --}}
        @if($stats['by_method']->isNotEmpty())
            <div>
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Cobrado por método de pago</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($stats['by_method'] as $methodName => $amount)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">{{ $methodName }}</span>
                            <span class="text-gray-500 dark:text-gray-400">${{ number_format($amount, 2) }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>