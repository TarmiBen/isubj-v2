<div>
    <div class="w-full overflow-x-auto">
        @if($students && $students->count() > 0)
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            #
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Apellido Paterno
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Apellido Materno
                        </th>

                        {{-- Mostrar columnas de unidades solo si puede ver calificaciones --}}
                        @can('Ver calificaciones')
                            @foreach($units as $unity)
                                <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Calificación {{ $unity->name }}
                                </th>
                            @endforeach

                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nota Final
                            </th>
                        @endcan

                        {{-- Mostrar header de acciones solo a quienes pueden editar --}}
                        @can('Editar calificaciones')
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        @endcan

                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($students as $index => $student)


                        <tr class=" dark:hover:bg-gray-800 transition-colors" >
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">

                                            {{ $student->name }}

                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                           {{ $student->last_name1 }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                           {{ $student->last_name2 }}
                                        </div>
                                    </div>
                                </div>
                            </td>


                            {{-- Mostrar las calificaciones por unidad solo si puede ver calificaciones --}}
                            @can('Ver calificaciones')
                                @foreach($units as $unity)
                                    @php
                                        // Optimización: usar cache local para evitar consultas repetidas
                                        static $qualificationsCache = [];
                                        $cacheKey = "student_{$student->id}_unit_{$unity->id}";

                                        if (!isset($qualificationsCache[$cacheKey])) {
                                            $qualification = \App\Models\Qualification::where('student_id', $student->id)
                                                ->where('unity_id', $unity->id)
                                                ->first();
                                            $qualificationsCache[$cacheKey] = $qualification;
                                        } else {
                                            $qualification = $qualificationsCache[$cacheKey];
                                        }

                                        $score = $qualification ? $qualification->score : null;
                                    @endphp
                                    <td class="px-3 py-4 whitespace-nowrap text-center">
                                        <div class="w-16 h-8 {{ $score ? 'bg-green-100 dark:bg-green-900 border-green-300 dark:border-green-600' : 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600' }} border rounded flex items-center justify-center">
                                            <span class="text-xs {{ $score ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-400 dark:text-gray-500' }}">
                                                {{ $score ?? '-' }}
                                            </span>
                                        </div>
                                    </td>
                                @endforeach

                                {{-- Calificación final automática desde base de datos --}}
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                    @php
                                        // Obtener la calificación final guardada
                                        $finalGrade = \App\Models\FinalGrade::getLatestGrade($student->id, $assignment->id);
                                    @endphp

                                    @if($finalGrade)
                                        <div class="flex items-center justify-center space-x-2">
                                            <div class="w-16 h-8 {{ $finalGrade->getGradeBgColorClass() }} border rounded flex items-center justify-center">
                                                <span class="text-xs {{ $finalGrade->getGradeColorClass() }} font-semibold">
                                                    {{ $finalGrade->grade }}
                                                </span>
                                            </div>

                                            @if($finalGrade->attempt_type)
                                                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                                                    {{ $finalGrade->attempt_type }}
                                                </span>
                                            @endif

                                            @can('Editar calificaciones')
                                                <button
                                                    type="button"
                                                    wire:click="openEditFinalGradeModal({{ $finalGrade->id }})"
                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
                                                    title="Editar calificación final"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>


                                        @if($finalGrade->isFailed() && \App\Models\FinalGrade::canHaveMoreAttempts($student->id, $assignment->id))

                                                <div class="mt-1 text-red-600 dark:text-red-400">
                                                    <button
                                                        type="button"
                                                        wire:click="openNewAttemptModal({{ $student->id }})"
                                                        class="text-xs bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 hover:bg-orange-200 dark:hover:bg-orange-800 px-2 py-1 rounded transition-colors"
                                                        title="Calificación Extraordinaria"
                                                    >
                                                        Calificación Extraordinaria
                                                    </button>
                                                </div>
                                        @endif
                                    @else
                                        {{-- No hay calificación final (se calculará automáticamente cuando estén todas las unidades) --}}
                                        <div class="w-16 h-8 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded flex items-center justify-center">
                                            <span class="text-xs text-gray-400 dark:text-gray-500" title="Se calculará automáticamente cuando todas las unidades estén calificadas">--</span>
                                        </div>
                                    @endif
                                </td>
                            @endcan

                            {{-- Celda de acciones visible solo si puede editar --}}
                            @can('Editar calificaciones')
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                  @if(!$finalGrade || $finalGrade->getAttemptTypeAttribute() == '')
                                    <button
                                        type="button"
                                        wire:click="openGradesModal({{ $student->id }})"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Calificar
                                    </button>
                                    @endif
                                </td>
                            @endcan

                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <x-heroicon-o-users class="w-6 h-6 text-gray-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    No hay estudiantes matriculados
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Este grupo no tiene estudiantes inscritos actualmente.
                </p>
            </div>
        @endif
    </div>

    {{-- Información adicional sobre los créditos --}}
    @if($students && $students->count() > 0)
        @can('Ver calificaciones')
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        Información de Calificaciones
                    </span>
                </div>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                    <p>• Esta materia tiene <strong>{{ count($units) ?? 0 }} crédito(s)</strong></p>
                    <p>• Total de estudiantes: <strong>{{ $students->count() }}</strong></p>
                </div>
            </div>
        @endcan
    @endif

    {{-- Modal de Calificaciones --}}
    @can('Editar calificaciones')
        @if($showModal && $selectedStudent)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

                    <!-- This element is to trick the browser into centering the modal contents. -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
                    >
                        <form wire:submit.prevent="saveGrades">
                            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <!-- Header -->
                                        <div class="flex items-center justify-between mb-6">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-4">
                                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                        {{ substr($selectedStudent->name ?? '', 0, 1) }}{{ substr($selectedStudent->last_name1 ?? '', 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                                        Calificaciones de {{ $selectedStudent->name }} {{ $selectedStudent->last_name1 }} {{ $selectedStudent->last_name2 }}
                                                    </h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $assignment->subject->name }} - {{ $assignment->group->code }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Grades Input -->
                                        <div class="space-y-4">
                                            @foreach($units as $unit)
                                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4" x-data="{ showComment_{{ $unit->id }}: false }">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label for="grade_{{ $unit->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                                Calificación - {{ $unit->name }}
                                                            </label>
                                                            <input
                                                                type="number"
                                                                id="grade_{{ $unit->id }}"
                                                                wire:model="grades.{{ $unit->id }}"
                                                                min="0"
                                                                max="100"
                                                                step="0.01"
                                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                                                placeholder="0.00"
                                                            >
                                                            @error('grades.' . $unit->id)
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                            @enderror
                                                        </div>

                                                        <!-- Botón para mostrar/ocultar comentarios -->
                                                        <div>
                                                            <button
                                                                type="button"
                                                                @click="showComment_{{ $unit->id }} = !showComment_{{ $unit->id }}"
                                                                class=" "
                                                            >

                                                                <span class="text-blue-600"  x-text="showComment_{{ $unit->id }} ? 'Ocultar comentario' : 'Agregar comentario'"></span>
                                                            </button>
                                                        </div>

                                                        <!-- Sección de comentarios (oculta por defecto) -->
                                                        <div x-show="showComment_{{ $unit->id }}" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95">
                                                            <label for="comment_{{ $unit->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                                Comentarios
                                                            </label>
                                                            <textarea
                                                                id="comment_{{ $unit->id }}"
                                                                wire:model="comments.{{ $unit->id }}"
                                                                rows="3"
                                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                                                placeholder="Comentarios sobre esta unidad..."
                                                            ></textarea>
                                                            @error('comments.' . $unit->id)
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    <span wire:loading.remove>Guardar Calificaciones</span>
                                    <span wire:loading>Guardando...</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Modal de Calificación Final --}}

        @if($showFinalGradeModal && $selectedStudentForFinal)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="final-modal-title"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeFinalGradeModal"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                    >
                        <form wire:submit.prevent="{{ $isEditingFinalGrade ? 'saveFinalGrade' : 'saveNewAttempt' }}">
                            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <!-- Header -->
                                        <div class="flex items-center justify-between mb-6">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-4">
                                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                        {{ substr($selectedStudentForFinal->name ?? '', 0, 1) }}{{ substr($selectedStudentForFinal->last_name1 ?? '', 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="final-modal-title">
                                                        {{ $isEditingFinalGrade ? 'Editar' : 'Registrar' }} Calificación Final
                                                    </h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $selectedStudentForFinal->name }} {{ $selectedStudentForFinal->last_name1 }} {{ $selectedStudentForFinal->last_name2 }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="closeFinalGradeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Final Grade Input -->
                                        <div class="space-y-4">
                                            <div>
                                                <label for="finalGrade" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Calificación Final (0.0 - 10.0)
                                                </label>
                                                <input
                                                    type="number"
                                                    id="finalGrade"
                                                    wire:model="finalGrade"
                                                    min="0"
                                                    max="10"
                                                    step="0.1"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                                    placeholder="7.5"
                                                    required
                                                >
                                                @error('finalGrade')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            @if(!$isEditingFinalGrade)
                                                @php
                                                    $nextAttempt = (\App\Models\FinalGrade::where('student_id', $selectedStudentForFinal->id ?? 0)
                                                        ->where('assignment_id', $assignment->id ?? 0)
                                                        ->max('attempt') ?? 0) + 1;
                                                @endphp
                                                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                                                    <div class="flex items-center space-x-2">
                                                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                        </svg>
                                                        <span class="text-sm font-medium text-orange-800 dark:text-orange-300">
                                                            Nuevo Intento #{{ $nextAttempt }}
                                                            @if($nextAttempt == 2) (Extraordinario)
                                                            @elseif($nextAttempt == 3) (Título de Suficiencia)
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 text-xs text-orange-700 dark:text-orange-400">
                                                        @if($nextAttempt <= 3)
                                                            <p>• Calificación mínima aprobatoria: <strong>7.0</strong></p>
                                                            @if($nextAttempt < 3)
                                                                <p>• Si reprueba, podrá tener {{ 3 - $nextAttempt }} intento(s) más</p>
                                                            @else
                                                                <p>• Este es el último intento disponible</p>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                                    <div class="flex items-center space-x-2">
                                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                                            Editar Calificación Final
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 text-xs text-blue-700 dark:text-blue-400">
                                                        <p>• Modificando una calificación existente</p>
                                                        <p>• Calificación mínima aprobatoria: <strong>7.0</strong></p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 {{ $isEditingFinalGrade ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500' : 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500' }} text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    <span wire:loading.remove>{{ $isEditingFinalGrade ? 'Actualizar' : 'Registrar Intento' }}</span>
                                    <span wire:loading>{{ $isEditingFinalGrade ? 'Actualizando...' : 'Registrando...' }}</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="closeFinalGradeModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium  hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

</div>
