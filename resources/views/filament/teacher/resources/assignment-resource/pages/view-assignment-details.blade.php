<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Información de la Asignación
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Grupo</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->group->code }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Profesor</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->teacher->name ?? 'No asignado' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Materia</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->subject->name ?? 'No asignada' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Carrera</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->subject->career->name ?? 'No asignada' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Carrera</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->subject->career->modality->name ?? 'No asignada' }}</p>
                </div>
            </div>
        </x-filament::section>

        <!-- Documentos -->
        <x-filament::section>
            <x-slot name="heading">
                Documentos de la Asignación
            </x-slot>

            <div class="mt-4">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
