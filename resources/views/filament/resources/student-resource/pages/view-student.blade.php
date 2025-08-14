<x-filament::page>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4">Detalles del Estudiante</h2>

        <div>
            <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                <div>
                    <p><strong>Carrera:</strong> {{$record->lastInscription?->group?->generation->career?->name}}</p>
                    <p><strong>Grupo:</strong>{{ $record->lastInscription?->group?->code ?? 'N/A' }}</p>
                    <p><strong>Correo:</strong>{{$record->user->email ?? 'N/A'}}</p>
                    <p><strong>Ultima sesión:</strong></p>
                </div>
                <div class="grid grid-rows-2 gap-4">
                    <h2 class="text-xl font-extrabold">Datos Personales:</h2>
                    <p><strong>Matrícula:</strong> {{ $record->student_number }}</p>
                    <p><strong>Nombre:</strong> {{ $record->fullname }}</p>
                    <p><strong>CURP:</strong> {{ $record->curp }}</p>
                    <p><strong>Correo:</strong> {{ $record->email}}</p>
                    <p><strong>Celular:</strong> {{ $record->phone }}</p>
                    <p><strong>Dirección:</strong> {{ $record->street }}, {{ $record->city }}, {{ $record->state }}, CP {{ $record->postal_code }}, {{ $record->country }}</p>
                    <p><strong>Fecha de nacimiento:</strong> {{ $record->date_of_birth?->format('d/m/Y') ?? 'N/A' }}</p>
                    <p><strong>Estatus:</strong>
                        @if (strtolower($record->status) === 'activo' || strtolower($record->status) === 'active')
                            <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">{{ ucfirst($record->status) }}</span>
                        @else
                            <span class="inline-block px-2 py-1 text-xs bg-red-100 text-red-800 rounded">{{ ucfirst($record->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>


            <div class="grid grid-cols-2 gap-4">
                <div class="text-primary text-3xl">Inscripciones
                    @forelse ($record->inscriptions as $inscription)
                        <p>
                            <span class="font-bold">{{ $inscription->group?->code ?? 'N/A' }}</span>
                            <span class="text-sm text-gray-600">({{ $inscription->group?->generation?->career?->name ?? 'N/A' }})</span>
                        </p>
                    @empty
                        <p class="text-sm text-gray-600">No hay inscripciones disponibles.</p>
                    @endforelse
                </div>
                <div class="bg-red-200 p-4">Documentos
                    @forelse ($record->documents as $document)
                        <p>
                            <span>{{ $document->name ?? 'Documento' }}</span>
                            <a href="{{ asset('storage/' . $document->src) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline mr-4"
                               type="application/pdf"
                            >
                                Ver
                            </a>
                            <a
                                href="{{ asset('storage/' . $document->src) }}"
                                target="_blank"
                                class="text-green-600 hover:underline"
                                download="{{ basename($document->src) }}"
                                type="application/pdf"
                            >
                                Descargar
                            </a>
                        </p>
                    @empty
                        <p class="text-sm text-gray-600">No hay documentos disponibles.</p>
                    @endforelse
                </div>
            </div>
        </div>



        <x-filament::button href="{{ route('filament.admin.resources.students.index') }}" class="mt-6">
            ← Volver
        </x-filament::button>
    </x-filament::card>
</x-filament::page>
