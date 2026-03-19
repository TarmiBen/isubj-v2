<div class="max-w-4xl mx-auto p-6">
    @if($currentStep === 'login')
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistema de Evaluación Docente</h1>
                <p class="text-gray-600">Ingresa tu código de estudiante para comenzar</p>
            </div>

            @if(session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form wire:submit.prevent="validateStudent" class="space-y-4">
                <div>
                    <label for="studentCode" class="block text-sm font-medium text-gray-700 mb-2">
                        Código de Estudiante
                    </label>
                    <input
                        type="text"
                        id="studentCode"
                        wire:model="studentCode"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ingresa tu código"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-4 focus:ring-blue-200"
                >
                    Continuar
                </button>
            </form>
        </div>

    @elseif($currentStep === 'subjects')
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    Bienvenido {{ $student->full_name }}
                </h2>
                <p class="text-gray-600">
                    Grupo: {{ $student->lastInscription->group->code ?? 'Sin grupo' }}
                </p>
                <p class="text-sm text-gray-500">
                    Ciclo: {{ $cycle->name }}
                </p>
            </div>

            @if(session()->has('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Selecciona la asignatura a evaluar:
                </h3>

                <div class="grid gap-4">
                    @foreach($assignments as $assignment)
                        <div class="border rounded-lg p-4 {{ in_array($assignment->id, $completedAssignments) ? 'bg-green-50 border-green-200' : 'hover:bg-gray-50' }}">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-semibold text-gray-800">
                                        {{ $assignment->subject->name }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Profesor: {{ $assignment->teacher->name ?? 'No asignado' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Código: {{ $assignment->subject->code }}
                                    </p>
                                </div>
                                <div>
                                    @if(in_array($assignment->id, $completedAssignments))
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                            ✓ Completado
                                        </span>
                                    @else
                                        <button
                                            wire:click="startSurvey({{ $assignment->id }})"
                                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:ring-4 focus:ring-blue-200"
                                        >
                                            Evaluar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Progreso: {{ count($completedAssignments) }}/{{ count($assignments) }} evaluaciones completadas
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ count($assignments) > 0 ? (count($completedAssignments) / count($assignments)) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>

    @elseif($currentStep === 'survey')
        <div class="bg-white rounded-lg shadow-lg p-8">
            @if($questions->count() > 0)
                @php $currentQuestion = $questions[$currentQuestionIndex] @endphp

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Evaluando: {{ $currentAssignment->subject->name }}
                        </h3>
                        <span class="text-sm text-gray-500">
                            {{ $currentQuestionIndex + 1 }}/{{ $questions->count() }}
                        </span>
                    </div>

                    {{-- Navegación de preguntas --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($questions as $index => $question)
                            <button
                                wire:click="goToQuestion({{ $index }})"
                                class="w-8 h-8 rounded-full text-sm font-medium transition-all duration-200
                                    {{ $index === $currentQuestionIndex ? 'bg-blue-600 text-white ring-2 ring-blue-200' : '' }}
                                    {{ isset($answers[$question->id]) && $answers[$question->id] !== null && $answers[$question->id] !== '' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                            >
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ (($currentQuestionIndex + 1) / $questions->count()) * 100 }}%"></div>
                    </div>
                </div>

                <div class="mb-8">
                    <h4 class="text-lg font-medium text-gray-800 mb-4">
                        {{ $currentQuestion->question }}
                        @if($currentQuestion->required)
                            <span class="text-red-500">*</span>
                        @endif
                    </h4>

                    @if($currentQuestion->type === 'scale')
                        <div class="space-y-2" wire:key="scale-{{ $currentQuestion->id }}">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Muy malo</span>
                                <span>Excelente</span>
                            </div>
                            <div class="flex space-x-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @php
                                        $isSelected = isset($answers[$currentQuestion->id]) && $answers[$currentQuestion->id] == $i;
                                    @endphp
                                    <label class="flex-1" wire:key="option-{{ $currentQuestion->id }}-{{ $i }}">
                                        <input
                                            type="radio"
                                            name="question_{{ $currentQuestion->id }}"
                                            wire:model.live="answers.{{ $currentQuestion->id }}"
                                            value="{{ $i }}"
                                            class="sr-only"
                                            {{ $isSelected ? 'checked' : '' }}
                                        >
                                        <div class="text-center p-3 border-2 rounded cursor-pointer transition-all duration-200
                                            {{ $isSelected ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50' }}">
                                            <span class="text-lg font-semibold {{ $isSelected ? 'text-blue-600' : 'text-gray-700' }}">{{ $i }}</span>
                                        </div>
                                    </label>
                                @endfor
                            </div>
                            @if(isset($answers[$currentQuestion->id]) && $answers[$currentQuestion->id] !== null)
                                <div class="text-center text-sm text-blue-600 mt-2">
                                    Calificación seleccionada: {{ $answers[$currentQuestion->id] }}
                                </div>
                            @endif
                        </div>

                    @elseif($currentQuestion->type === 'text')
                        <textarea
                            wire:model.blur="answers.{{ $currentQuestion->id }}"
                            wire:key="textarea-{{ $currentQuestion->id }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            rows="4"
                            placeholder="Escribe tu respuesta aquí..."
                        >{{ $answers[$currentQuestion->id] ?? '' }}</textarea>
                    @endif
                </div>

                <div class="flex justify-between">
                    <button
                        wire:click="previousQuestion"
                        @if($currentQuestionIndex === 0) disabled @endif
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Anterior
                    </button>

                    <button
                        wire:click="backToSubjects"
                        class="px-6 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Volver a Asignaturas
                    </button>

                    <button
                        wire:click="nextQuestion"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-4 focus:ring-blue-200"
                    >
                        {{ $currentQuestionIndex === $questions->count() - 1 ? 'Finalizar' : 'Siguiente' }}
                    </button>
                </div>
            @endif
        </div>

    @elseif($currentStep === 'completed')
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    ¡Ya has completado todas las evaluaciones!
                </h2>
                <p class="text-gray-600">
                    Has evaluado todas las asignaturas para este ciclo.
                </p>
            </div>

            <p class="text-sm text-gray-500 mb-4">
                Gracias por tu participación en el proceso de evaluación docente.
            </p>

            <button
                wire:click="backToSubjects"
                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
            >
                Ver Evaluaciones
            </button>
        </div>

    @elseif($currentStep === 'all_completed')
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    ¡Evaluación Completada!
                </h2>
                <p class="text-gray-600">
                    Has completado todas las evaluaciones para este ciclo.
                </p>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 font-medium">
                    {{ count($completedAssignments) }}/{{ count($assignments) }} asignaturas evaluadas
                </p>
            </div>

            <button
                wire:click="backToSubjects"
                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
            >
                Ver Resumen
            </button>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para actualizar el estado visual de los radio buttons
        function updateRadioStates() {
            const radios = document.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                const parent = radio.closest('label');
                if (parent) {
                    const div = parent.querySelector('div');
                    const span = div?.querySelector('span');

                    if (radio.checked) {
                        // Marcar como seleccionado
                        div?.classList.remove('border-gray-300');
                        div?.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
                        span?.classList.remove('text-gray-700');
                        span?.classList.add('text-blue-600');
                    } else {
                        // Marcar como no seleccionado
                        div?.classList.remove('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
                        div?.classList.add('border-gray-300');
                        span?.classList.remove('text-blue-600');
                        span?.classList.add('text-gray-700');
                    }
                }
            });
        }

        // Actualizar cuando se hace clic en un radio button
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                setTimeout(updateRadioStates, 10);
            }
        });

        // Actualizar cuando se hace clic en el div (label)
        document.addEventListener('click', function(e) {
            if (e.target.closest('label')) {
                const radio = e.target.closest('label').querySelector('input[type="radio"]');
                if (radio) {
                    setTimeout(updateRadioStates, 10);
                }
            }
        });

        // Actualizar al cargar inicialmente
        setTimeout(updateRadioStates, 100);
    });

    // Para compatibilidad con Livewire
    document.addEventListener('livewire:navigated', function() {
        setTimeout(function() {
            const radios = document.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                const parent = radio.closest('label');
                if (parent) {
                    const div = parent.querySelector('div');
                    const span = div?.querySelector('span');

                    if (radio.checked) {
                        div?.classList.remove('border-gray-300');
                        div?.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
                        span?.classList.remove('text-gray-700');
                        span?.classList.add('text-blue-600');
                    }
                }
            });
        }, 100);
    });
</script>

