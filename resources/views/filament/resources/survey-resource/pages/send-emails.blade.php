<x-filament-panels::page>
    <div class="space-y-6">
        @if($currentCycle)
            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $totalStudents }}</div>
                        <div class="text-sm text-gray-600">Estudiantes Elegibles</div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ $emailsSent }}</div>
                        <div class="text-sm text-gray-600">Correos Enviados</div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ $currentCycle->name }}</div>
                        <div class="text-sm text-gray-600">Ciclo Actual</div>
                    </div>
                </x-filament::card>
            </div>

            <!-- Configuración del Correo -->
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold">Configuración del Correo</h3>
                </x-slot>

                <form wire:submit.prevent="sendEmails" class="space-y-4">
                    <div>
                        <label for="emailSubject" class="block text-sm font-medium  mb-2">
                            Asunto del Correo
                        </label>
                        <input
                            type="text"
                            id="emailSubject"
                            wire:model="emailSubject"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            required
                        >

                    </div>

                    <div>
                        <label for="emailBody" class="block text-sm font-medium  mb-2">
                            Contenido del Correo
                        </label>
                        <div class="mb-2 text-xs text-gray-500">
                            Puedes usar las siguientes variables que serán reemplazadas automáticamente:
                            <code class="bg-gray-100 px-1 rounded">{{'{student_id}'}}</code> y
                            <code class="bg-gray-100 px-1 rounded">{{'{survey_url}'}}</code>
                        </div>
                        <textarea
                            id="emailBody"
                            wire:model="emailBody"
                            rows="15"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            required
                        ></textarea>
                    </div>
                </form>
            </x-filament::card>

            <!-- Vista Previa -->
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold">Vista Previa del Correo</h3>
                </x-slot>

                <div class="border-l-4 border-blue-400 bg-blue-50 p-4 rounded-r-lg">
                    <div class="mb-4">
                        <strong class="text-gray-700">Asunto:</strong> {{ $emailSubject }}
                    </div>
                    <div class="text-gray-800 whitespace-pre-line">
                        {{ str_replace(['{student_id}', '{survey_url}'], ['[CÓDIGO_ESTUDIANTE]', '[ENLACE_EVALUACIÓN]'], $emailBody) }}
                    </div>
                </div>
            </x-filament::card>

            <!-- Lista de Estudiantes -->
            @if(count($eligibleStudents) > 0)
                <x-filament::card>
                    <x-slot name="header">
                        <h3 class="text-lg font-semibold">Estudiantes Elegibles ({{ count($eligibleStudents) }})</h3>
                    </x-slot>

                    <div class="max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($eligibleStudents as $student)
                                <div class="border rounded-lg p-3 hover:bg-gray-50">
                                    <div class="font-medium text-gray-900">
                                        {{ $student->full_name }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $student->email }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Código: {{ $student->code ?? $student->id }}
                                    </div>
                                    @if($student->lastInscription && $student->lastInscription->group)
                                        <div class="text-xs text-blue-600">
                                            Grupo: {{ $student->lastInscription->group->code }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-filament::card>
            @endif

            <!-- Advertencias -->
            <x-filament::card>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Importante:</strong> Esta acción enviará {{ $totalStudents }} correos electrónicos.
                                Asegúrate de que el contenido y la configuración sean correctos antes de proceder.
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>

        @else
            <x-filament::card>
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay ciclo activo</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No se puede enviar correos sin un ciclo activo.
                        </p>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
