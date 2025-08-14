<div>
    <x-filament::button wire:click="openModal" color="primary">
        Inscribir estudiante
    </x-filament::button>

    <x-filament::modal wire:model="isOpen" max-width="md">
        <x-slot name="title">Nueva inscripción</x-slot>

        <form wire:submit.prevent="save" class="space-y-4 p-4">
            {{ $this->form }}

            <div class="flex justify-end gap-2 pt-4">
                <x-filament::button type="submit" color="primary">
                    Guardar
                </x-filament::button>

                <x-filament::button type="button" color="secondary" wire:click="$set('isOpen', false)">
                    Cancelar
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</div>
