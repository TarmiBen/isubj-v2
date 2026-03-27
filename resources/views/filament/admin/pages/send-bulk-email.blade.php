<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}
        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="submit"
                size="lg"
            >
                <x-heroicon-o-paper-airplane class="w-5 h-5 mr-2" />
                Enviar Correo
            </x-filament::button>
        </div>
    </form>
    <x-filament-actions::modals />
</x-filament-panels::page>
