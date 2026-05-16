<x-filament::page>

    <div class="space-y-8">

        @forelse($inscriptions as $inscription)
            @php
                $group   = $inscription->group;
                $period  = $group?->period;
                $career  = $period?->career;
                $cycle   = $inscription->cycle;
            @endphp

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

                {{-- Header de inscripción --}}
                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-100">
                                {{ $career?->name ?? 'Carrera no registrada' }}
                            </span>
                            @if($career?->code)
                                <span class="text-xs font-mono bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                                    {{ $career->code }}
                                </span>
                            @endif
                            <span class="text-xs px-2 py-0.5 rounded
                                {{ $inscription->status === 'active'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                {{ $inscription->status === 'active' ? 'Activa' : ucfirst($inscription->status) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                            <span>
                                <x-heroicon-o-academic-cap class="inline w-4 h-4 mr-1" />
                                {{ $period?->name ?? 'Periodo desconocido' }}
                            </span>
                            <span>
                                <x-heroicon-o-user-group class="inline w-4 h-4 mr-1" />
                                Grupo: {{ $group?->code ?? '—' }}
                            </span>
                            @if($cycle)
                                <span>
                                    <x-heroicon-o-calendar class="inline w-4 h-4 mr-1" />
                                    Ciclo: {{ $cycle->name ?? $cycle->id }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                Inscrito: {{ $inscription->created_at?->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('student.download-report-card-inscription', ['studentId' => $record->id, 'inscriptionId' => $inscription->id]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shrink-0">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        Descargar Boleta
                    </a>
                </div>

                {{-- Tabla de calificaciones --}}
                @php
                    $assignments = $group?->assignments ?? collect();
                @endphp

                @if($assignments->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Clave</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Materia</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Calificación Final</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                @foreach($assignments as $assignment)
                                    @php
                                        $finalGrade = $assignment->finalGrades->first();
                                        $grade = $finalGrade?->grade;
                                        $passed = $grade !== null && $grade >= 7;
                                        $attemptType = $finalGrade?->getAttemptTypeAttribute();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                            {{ $assignment->subject?->code ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">
                                            {{ $assignment->subject?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($grade !== null)
                                                <span class="inline-flex items-center justify-center w-12 h-8 rounded text-sm font-bold
                                                    {{ $passed
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                                    {{ $grade }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($attemptType)
                                                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                                                    {{ $attemptType }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($grade !== null)
                                                <span class="text-xs font-medium {{ $passed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $passed ? 'Aprobado' : 'Reprobado' }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500">Sin calificar</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            {{-- Promedio --}}
                            @php
                                $graded = $assignments->filter(fn($a) => $a->finalGrades->first()?->grade !== null);
                                $avg = $graded->isNotEmpty()
                                    ? round($graded->sum(fn($a) => $a->finalGrades->first()->grade) / $graded->count(), 1)
                                    : null;
                            @endphp
                            @if($avg !== null)
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td colspan="2" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                            Promedio general
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-12 h-8 rounded text-sm font-bold
                                                {{ $avg >= 7
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                                {{ $avg }}
                                            </span>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                        No hay materias registradas para este grupo.
                    </div>
                @endif

            </div>
        @empty
            <div class="text-center py-16">
                <x-heroicon-o-academic-cap class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <p class="text-gray-500 dark:text-gray-400">Este alumno no tiene inscripciones registradas.</p>
            </div>
        @endforelse

    </div>

</x-filament::page>
