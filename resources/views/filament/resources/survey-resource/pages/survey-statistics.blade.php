<x-filament-panels::page>
    <div class="space-y-6">
        @if($currentCycle)
            <!-- Resumen General -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $totalResponses }}</div>
                        <div class="text-sm text-gray-600">Total de Respuestas</div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">{{ count($teacherStatistics) }}</div>
                        <div class="text-sm text-gray-600">Docentes Evaluados</div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ count($questionStatistics) }}</div>
                        <div class="text-sm text-gray-600">Preguntas del Cuestionario</div>
                    </div>
                </x-filament::card>
            </div>

            <!-- Estadísticas por Docente -->
            @if(count($teacherStatistics) > 0)
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
                                @foreach($teacherStatistics as $stat)
                                    <tr class="border-b ">
                                        <td class="py-3 px-4 font-medium">{{ $stat['teacher_name'] }}</td>
                                        <td class="py-3 px-4">{{ $stat['subject_name'] }}</td>
                                        <td class="py-3 px-4 text-center">{{ $stat['total_responses'] }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($stat['average_score'] >= 4.5) bg-green-100 text-green-800
                                                @elseif($stat['average_score'] >= 3.5) bg-yellow-100 text-yellow-800
                                                @elseif($stat['average_score'] >= 2.5) bg-orange-100 text-orange-800
                                                @else bg-red-100 text-red-800
                                                @endif
                                            ">
                                                {{ $stat['average_score'] }}/5.0
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full
                                                    @if($stat['average_score'] >= 4.5) bg-green-600
                                                    @elseif($stat['average_score'] >= 3.5) bg-yellow-600
                                                    @elseif($stat['average_score'] >= 2.5) bg-orange-600
                                                    @else bg-red-600
                                                    @endif
                                                " style="width: {{ ($stat['average_score'] / 5) * 100 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>
            @endif

            <!-- Estadísticas por Pregunta -->
            @if(count($questionStatistics) > 0)
                <x-filament::card>
                    <x-slot name="header">
                        <h3 class="text-lg font-semibold">Estadísticas por Pregunta</h3>
                    </x-slot>

                    <div class="space-y-4">
                        @foreach($questionStatistics as $stat)
                            <div class="border rounded-lg p-4 ">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $stat['question'] }}</p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-sm text-gray-600">
                                                Tipo: <span class="font-medium">{{ ucfirst($stat['type']) }}</span>
                                            </span>
                                            <span class="text-sm text-gray-600">
                                                Respuestas: <span class="font-medium">{{ $stat['total_responses'] }}</span>
                                            </span>
                                            @if($stat['average_score'])
                                                <span class="text-sm text-gray-600">
                                                    Promedio: <span class="font-medium">{{ $stat['average_score'] }}/5.0</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="{{ \App\Filament\Resources\SurveyResource::getUrl('question-detail', ['record' => $record, 'question' => $stat['question_id']]) }}"
                                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Ver Detalle
                                        </a>
                                    </div>
                                </div>

                                @if($stat['average_score'])
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full
                                            @if($stat['average_score'] >= 4.5) bg-green-600
                                            @elseif($stat['average_score'] >= 3.5) bg-yellow-600
                                            @elseif($stat['average_score'] >= 2.5) bg-orange-600
                                            @else bg-red-600
                                            @endif
                                        " style="width: {{ ($stat['average_score'] / 5) * 100 }}%"></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
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
                            No se encontró un ciclo activo para mostrar las estadísticas.
                        </p>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
