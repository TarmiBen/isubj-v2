<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="md:col-span-2 space-y-6">

            <div class="rounded-lg p-4 flex items-center justify-between shadow-sm mb-6 bg-gray-100 dark:bg-gray-800">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                        <x-heroicon-o-user />
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $record->fullname }}</h1>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>📄 Matrícula: {{ $record->student_number }}</span>
                            @if (strtolower($record->status) === 'activo' || strtolower($record->status) === 'active')
                                <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800
                                                 dark:bg-green-400 dark:text-green-400">
                                    {{ ucfirst($record->status) }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-800
                                                 dark:bg-red-900 dark:text-red-300">
                                    {{ ucfirst($record->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('filament.admin.resources.students.edit', $record) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-sm px-4 py-2 rounded flex items-center  text-green-400 space-x-1">
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
                    <p><strong>Carrera:</strong>
                        {{ $record->lastInscription?->group?->generation->career?->name ?? 'N/A' }}</p>
                    <p><strong>Grupo:</strong> {{ $record->lastInscription?->group?->code ?? 'N/A' }}</p>
                    <p><strong>Última sesión:</strong> No registrada</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">

            <div class="rounded-lg p-6 shadow-sm bg-gray-100 dark:bg-gray-800">
                <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Inscripciones</h2>
                @forelse ($record->inscriptions as $inscription)
                    <div class="p-3 mb-2 rounded border border-gray-200 dark:border-gray-700
                                    bg-white dark:bg-gray-900">
                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $inscription->group?->code ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $inscription->group?->generation?->career?->name ?? 'N/A' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">No hay inscripciones disponibles.</p>
                @endforelse
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
