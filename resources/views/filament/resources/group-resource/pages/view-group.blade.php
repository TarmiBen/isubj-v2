<x-filament::page>

    @php
        $period = $group->period;
        $career = $period?->career;
        $cycle = $group->cycle;
    @endphp

    <div class="space-y-6">

        {{-- Header del grupo --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-lg font-bold text-gray-800 dark:text-gray-100">
                            Grupo {{ $group->code }}
                        </span>
                        @if($career)
                            <span class="text-xs font-mono bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                                {{ $career->code ?? $career->name }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                        <span>
                            <x-heroicon-o-academic-cap class="inline w-4 h-4 mr-1" />
                            {{ $career?->name ?? 'Carrera no registrada' }}
                        </span>
                        <span>
                            <x-heroicon-o-calendar class="inline w-4 h-4 mr-1" />
                            {{ $period?->name ?? 'Periodo desconocido' }}
                        </span>
                        @if($cycle)
                            <span>
                                <x-heroicon-o-clock class="inline w-4 h-4 mr-1" />
                                Ciclo: {{ $cycle->name ?? $cycle->id }}
                            </span>
                        @endif
                        <span>
                            <x-heroicon-o-users class="inline w-4 h-4 mr-1" />
                            {{ $students->count() }} {{ $students->count() === 1 ? 'alumno' : 'alumnos' }}
                        </span>
                    </div>
                </div>

                <div class="text-center">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Promedio de Grupo</div>
                    @if($groupAverage !== null)
                        <span class="inline-flex items-center justify-center w-16 h-10 rounded-lg text-lg font-bold
                            {{ $groupAverage >= 7
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                            {{ $groupAverage }}
                        </span>
                    @else
                        <span class="text-sm text-gray-400 dark:text-gray-500">Sin datos</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Matriz alumnos x materias --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            @if($assignments->isEmpty())
                <div class="px-6 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                    Este grupo no tiene materias asignadas.
                </div>
            @elseif($students->isEmpty())
                <div class="px-6 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                    Este grupo no tiene alumnos activos inscritos.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                    Alumno
                                </th>
                                @foreach($assignments as $assignment)
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[110px]">
                                        <a href="{{ \App\Filament\Resources\AssignmentResource::getUrl('view', ['record' => $assignment]) }}"
                                           class="hover:text-primary-600 dark:hover:text-primary-400 hover:underline">
                                            {{ $assignment->subject?->name ?? 'Sin materia' }}
                                        </a>
                                        @if($assignment->subject?->code)
                                            <div class="font-mono text-[10px] text-gray-400 dark:text-gray-500 normal-case">
                                                {{ $assignment->subject->code }}
                                            </div>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                    Promedio
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($students as $student)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-4 py-3 whitespace-nowrap">
                                        <a href="{{ \App\Filament\Resources\StudentResource::getUrl('view', ['record' => $student]) }}"
                                           class="font-medium text-gray-800 dark:text-gray-200 hover:text-primary-600 dark:hover:text-primary-400 hover:underline">
                                            {{ $student->name }} {{ $student->last_name1 }} {{ $student->last_name2 }}
                                        </a>
                                    </td>
                                    @foreach($assignments as $assignment)
                                        @php
                                            $finalGrade = $finalGrades->get("{$student->id}-{$assignment->id}");
                                            $grade = $finalGrade?->grade;
                                            $passed = $grade !== null && (float) $grade >= 7;
                                        @endphp
                                        <td class="px-3 py-3 text-center">
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
                                    @endforeach
                                    @php
                                        $studentAverage = $studentAverages[$student->id] ?? null;
                                    @endphp
                                    <td class="px-4 py-3 text-center">
                                        @if($studentAverage !== null)
                                            <span class="inline-flex items-center justify-center w-14 h-8 rounded text-sm font-bold
                                                {{ $studentAverage >= 7
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                                {{ $studentAverage }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase whitespace-nowrap">
                                    Promedio de grupo
                                </td>
                                <td colspan="{{ $assignments->count() }}"></td>
                                <td class="px-4 py-3 text-center">
                                    @if($groupAverage !== null)
                                        <span class="inline-flex items-center justify-center w-14 h-8 rounded text-sm font-bold
                                            {{ $groupAverage >= 7
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                            {{ $groupAverage }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    </div>

</x-filament::page>