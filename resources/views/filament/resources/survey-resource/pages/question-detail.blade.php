<x-filament-panels::page>
    <div class="space-y-6">
        @if($currentCycle)
            <!-- Información de la Pregunta -->
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold">Detalles de la Pregunta</h3>
                </x-slot>

                <div class="space-y-4">
                    <div>
                        <p class="text-lg font-medium text-gray-900">{{ $question->question }}</p>
                        <div class="flex items-center space-x-4 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($question->type) }}
                            </span>
                            <span class="text-sm text-gray-600">
                                Orden: {{ $question->order }}
                            </span>
                            <span class="text-sm text-gray-600">
                                {{ $question->required ? 'Obligatoria' : 'Opcional' }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-filament::card>

            @if($question->type === 'scale')
                <!-- Estadísticas Generales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600">{{ $questionStatistics['total_responses'] }}</div>
                            <div class="text-sm text-gray-600">Total de Respuestas</div>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success-600">{{ $questionStatistics['average_score'] }}</div>
                            <div class="text-sm text-gray-600">Promedio General</div>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-info-600">{{ count($questionStatistics['by_teacher']) }}</div>
                            <div class="text-sm text-gray-600">Docentes Evaluados</div>
                        </div>
                    </x-filament::card>
                </div>

                <!-- Distribución de Calificaciones -->
                <x-filament::card>
                    <x-slot name="header">
                        <h3 class="text-lg font-semibold">Distribución de Calificaciones</h3>
                    </x-slot>

                    <div class="grid grid-cols-5 gap-4">
                        @foreach($questionStatistics['score_distribution'] as $score => $data)
                            <div class="text-center">
                                <div class="text-2xl font-bold
                                    @if($score == 5) text-green-600
                                    @elseif($score == 4) text-blue-600
                                    @elseif($score == 3) text-yellow-600
                                    @elseif($score == 2) text-orange-600
                                    @else text-red-600
                                    @endif
                                ">
                                    {{ $data['count'] }}
                                </div>
                                <div class="text-sm text-gray-600">{{ $score }} estrella{{ $score != 1 ? 's' : '' }}</div>
                                <div class="text-xs text-gray-500">{{ $data['percentage'] }}%</div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                    <div class="h-2 rounded-full
                                        @if($score == 5) bg-green-600
                                        @elseif($score == 4) bg-blue-600
                                        @elseif($score == 3) bg-yellow-600
                                        @elseif($score == 2) bg-orange-600
                                        @else bg-red-600
                                        @endif
                                    " style="width: {{ $data['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                <!-- Estadísticas por Docente -->
                @if(count($questionStatistics['by_teacher']) > 0)
                    <x-filament::card>
                        <x-slot name="header">
                            <h3 class="text-lg font-semibold">Calificaciones por Docente</h3>
                        </x-slot>

                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 px-4">Docente</th>
                                        <th class="text-left py-2 px-4">Asignatura</th>
                                        <th class="text-center py-2 px-4">Respuestas</th>
                                        <th class="text-center py-2 px-4">Promedio</th>
                                        <th class="text-center py-2 px-4">Calificación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questionStatistics['by_teacher'] as $stat)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium">{{ $stat['teacher_name'] }}</td>
                                            <td class="py-3 px-4">{{ $stat['subject_name'] }}</td>
                                            <td class="py-3 px-4 text-center">{{ $stat['count'] }}</td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($stat['average'] >= 4.5) bg-green-100 text-green-800
                                                    @elseif($stat['average'] >= 3.5) bg-yellow-100 text-yellow-800
                                                    @elseif($stat['average'] >= 2.5) bg-orange-100 text-orange-800
                                                    @else bg-red-100 text-red-800
                                                    @endif
                                                ">
                                                    {{ $stat['average'] }}/5.0
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full
                                                        @if($stat['average'] >= 4.5) bg-green-600
                                                        @elseif($stat['average'] >= 3.5) bg-yellow-600
                                                        @elseif($stat['average'] >= 2.5) bg-orange-600
                                                        @else bg-red-600
                                                        @endif
                                                    " style="width: {{ ($stat['average'] / 5) * 100 }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::card>
                @endif

            @else
                <!-- Respuestas de Texto -->
                <x-filament::card>
                    <x-slot name="header">
                        <h3 class="text-lg font-semibold">Respuestas de Texto ({{ count($textResponses) }})</h3>
                    </x-slot>

                    @if(count($textResponses) > 0)
                        <div class="space-y-4">
                            @foreach($textResponses as $response)
                                <div class="border-l-4 border-blue-400 pl-4 py-3 bg-gray-50 rounded-r-lg">
                                    <p class="text-gray-800 mb-2">"{{ $response['text'] }}"</p>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">{{ $response['teacher_name'] }}</span> -
                                        <span>{{ $response['subject_name'] }}</span> -
                                        <span>{{ $response['created_at']->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10m0 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m0 0v10a2 2 0 002 2h6a2 2 0 002-2V8m0 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">No hay respuestas de texto para esta pregunta.</p>
                            </div>
                        </div>
                    @endif
                </x-filament::card>
            @endif

        @else
            <x-filament::card>
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay ciclo activo</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No se encontró un ciclo activo para mostrar el detalle de la pregunta.
                        </p>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
