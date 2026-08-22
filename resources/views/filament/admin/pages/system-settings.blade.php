<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-o-check"
                wire:loading.attr="disabled"
            >
                Guardar configuración
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>