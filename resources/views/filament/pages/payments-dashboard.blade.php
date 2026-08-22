<x-filament::page>
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <span class="text-gray-500 dark:text-gray-400">Accesos rápidos:</span>

        @if($url = $this->getPaymentOrdersUrl())
            <a href="{{ $url }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <x-heroicon-o-document-currency-dollar class="w-4 h-4" />
                Ver Adeudos
            </a>
        @endif

        @if($url = $this->getPaymentsUrl())
            <a href="{{ $url }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <x-heroicon-o-banknotes class="w-4 h-4" />
                Ver historial de pagos
            </a>
        @endif
    </div>
</x-filament::page>