<x-filament::modal wire:model="isOpen" max-width="md">
    <x-slot name="title">Nueva inscripción</x-slot>
    <form wire:submit.prevent="save" class="space-y-4 p-4">

        <!-- Campo estudiante -->
        @if($student_id)
            <div>
                <label class="block text-sm font-medium">Estudiante</label>
                <input type="text" value="{{ $student_name }}" class="w-full border rounded px-2 py-1 bg-gray-100" readonly>
            </div>
        @else
            <div>
                <label for="student_id" class="block text-sm font-medium">Estudiante</label>
                <select wire:model="student_id" id="student_id" class="w-full border rounded px-2 py-1">
                    <option value="">Selecciona un estudiante</option>
                    @foreach($students as $student)
                        <option value="{{ $student['id'] }}">{{ $student['first_name'] }} {{ $student['last_name'] }}</option>
                    @endforeach
                </select>
                @error('student_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        @endif

        <!-- Campo grupo -->
        <div>
            <label for="group_id" class="block text-sm font-medium">Grupo</label>
            <select wire:model="group_id" id="group_id" class="w-full border rounded px-2 py-1">
                <option value="">Selecciona un grupo</option>
                @foreach($groups as $group)
                    <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                @endforeach
            </select>
            @error('group_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

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
