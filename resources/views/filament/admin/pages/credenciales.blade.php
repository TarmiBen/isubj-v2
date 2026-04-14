<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-o-arrow-down-tray"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="generate">Generar Credencial(es)</span>
                <span wire:loading wire:target="generate">Generando...</span>
            </x-filament::button>

            <span
                wire:loading
                wire:target="generate"
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                Procesando imágenes, por favor espere...
            </span>
        </div>
    </form>

    @script
    <script>
        $wire.on('open-download', ({ uuid }) => {
            window.location.href = '/admin/credenciales/download/' + uuid;
        });
    </script>
    @endscript
</x-filament-panels::page>