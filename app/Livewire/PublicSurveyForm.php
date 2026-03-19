<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyRelated;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PublicSurveyForm extends Component
{
    public $studentCode;
    public $student;
    public $currentStep = 'login';
    public $assignments = [];
    public $currentAssignment;
    public $survey;
    public $questions = [];
    public $answers = [];
    public $currentQuestionIndex = 0;
    public $cycle;
    public $surveyResponse;
    public $completedAssignments = [];

    public function mount($code = null)
    {
        if ($code) {
            $this->studentCode = $code;
            $this->validateStudent();
        }

        $this->cycle = Cycle::active()->current()->first() ?? Cycle::create([
            'name' => 'Ciclo 1',
            'code' => '1',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'active' => true,
            'description' => 'Ciclo por defecto'
        ]);
    }

    public function validateStudent()
    {
        $this->student = Student::where('student_number', $this->studentCode)
            ->orWhere('code', $this->studentCode)
            ->orWhere('id', $this->studentCode)
            ->first();

        if (!$this->student) {
            session()->flash('error', 'Código de estudiante no válido');
            return;
        }

        // Obtener grupo del estudiante
        $inscription = $this->student->lastInscription;

        if (!$inscription || !$inscription->group) {
            session()->flash('error', 'No tienes una inscripción activa');
            return;
        }

        // Obtener asignaturas del grupo
        $this->assignments = Assignment::with(['subject', 'teacher'])
            ->where('group_id', $inscription->group_id)
            ->get();

        if ($this->assignments->isEmpty()) {
            session()->flash('error', 'No hay asignaturas asignadas para tu grupo');
            return;
        }

        // Verificar qué asignaturas ya fueron contestadas
        $this->checkCompletedAssignments();

        if (count($this->completedAssignments) === $this->assignments->count()) {
            $this->currentStep = 'completed';
        } else {
            $this->currentStep = 'subjects';
        }
    }

    public function checkCompletedAssignments()
    {
        foreach ($this->assignments as $assignment) {
            $surveyRelated = SurveyRelated::where('survivable_type', Assignment::class)
                ->where('survivable_id', $assignment->id)
                ->where('cycle_id', $this->cycle->id)
                ->first();

            if ($surveyRelated) {
                $response = SurveyResponse::where('survey_related_id', $surveyRelated->id)
                    ->where('student_id', $this->student->id)
                    ->where('cycle_id', $this->cycle->id)
                    ->whereNotNull('completed_at')
                    ->first();

                if ($response) {
                    $this->completedAssignments[] = $assignment->id;
                }
            }
        }
    }

    public function startSurvey($assignmentId)
    {
        $this->currentAssignment = $this->assignments->find($assignmentId);

        if (!$this->currentAssignment) {
            session()->flash('error', 'Asignatura no encontrada');
            return;
        }

        // Verificar si ya fue contestada
        if (in_array($assignmentId, $this->completedAssignments)) {
            session()->flash('error', 'Ya has contestado esta evaluación');
            return;
        }

        // Obtener o crear SurveyRelated
        $surveyRelated = SurveyRelated::firstOrCreate([
            'survivable_type' => Assignment::class,
            'survivable_id' => $this->currentAssignment->id,
            'cycle_id' => $this->cycle->id,
        ], [
            'survey_id' => Survey::where('type', 'docente')->where('is_default', true)->first()->id ?? 1,
            'starts_at' => $this->cycle->starts_at,
            'ends_at' => $this->cycle->ends_at,
            'active' => true,
        ]);

        $this->survey = $surveyRelated->survey;
        $this->questions = $this->survey->questions()->ordered()->get();

        // Obtener o crear respuesta del estudiante
        $this->surveyResponse = SurveyResponse::firstOrCreate([
            'survey_related_id' => $surveyRelated->id,
            'student_id' => $this->student->id,
            'cycle_id' => $this->cycle->id,
        ], [
            'progress' => 0,
        ]);

        // Cargar respuestas existentes
        $this->loadExistingAnswers();

        $this->currentStep = 'survey';
        $this->currentQuestionIndex = 0;
    }

    public function loadExistingAnswers()
    {
        if (!$this->surveyResponse) {
            return;
        }

        $existingAnswers = SurveyAnswer::where('survey_response_id', $this->surveyResponse->id)
            ->get()
            ->keyBy('survey_question_id');

        $this->answers = []; // Limpiar respuestas anteriores

        foreach ($this->questions as $question) {
            if (isset($existingAnswers[$question->id])) {
                $answer = $existingAnswers[$question->id];
                $value = $answer->answer_numeric ?? $answer->answer_text;
                if ($value !== null) {
                    $this->answers[$question->id] = $value;
                }
            } else {
                // Asegurar que existe la clave para evitar errores
                $this->answers[$question->id] = null;
            }
        }
    }

    public function nextQuestion()
    {
        $this->saveCurrentAnswer();

        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
            $this->dispatch('questionChanged');
        } else {
            $this->completeSurvey();
        }
    }

    public function previousQuestion()
    {
        $this->saveCurrentAnswer();

        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->dispatch('questionChanged');
        }
    }

    public function goToQuestion($index)
    {
        $this->saveCurrentAnswer();

        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentQuestionIndex = $index;
        }

        // Forzar refresco del estado
        $this->dispatch('questionChanged');
    }

    public function saveCurrentAnswer()
    {
        if (!$this->surveyResponse || !isset($this->questions[$this->currentQuestionIndex])) {
            return;
        }

        $question = $this->questions[$this->currentQuestionIndex];
        $answer = $this->answers[$question->id] ?? null;

        // Solo guardar si hay una respuesta válida
        if ($answer !== null && $answer !== '') {
            try {
                SurveyAnswer::updateOrCreate([
                    'survey_response_id' => $this->surveyResponse->id,
                    'survey_question_id' => $question->id,
                ], [
                    'answer_numeric' => is_numeric($answer) ? $answer : null,
                    'answer_text' => is_numeric($answer) ? null : $answer,
                ]);

                // Actualizar progreso
                $totalAnswered = SurveyAnswer::where('survey_response_id', $this->surveyResponse->id)
                    ->whereNotNull(DB::raw('COALESCE(answer_numeric, answer_text)'))
                    ->count();

                $progress = round(($totalAnswered / count($this->questions)) * 100);

                $this->surveyResponse->update(['progress' => $progress]);
            } catch (\Exception $e) {
                logger('Error saving current answer: ' . $e->getMessage());
            }
        }
    }

    // Método para guardar automáticamente cuando cambia una respuesta
    public function updatedAnswers($value, $questionId)
    {
        if (!$this->surveyResponse || $value === null) {
            return;
        }

        try {
            SurveyAnswer::updateOrCreate([
                'survey_response_id' => $this->surveyResponse->id,
                'survey_question_id' => $questionId,
            ], [
                'answer_numeric' => is_numeric($value) ? $value : null,
                'answer_text' => is_numeric($value) ? null : $value,
            ]);

            // Actualizar progreso
            $totalAnswered = SurveyAnswer::where('survey_response_id', $this->surveyResponse->id)->count();
            $progress = round(($totalAnswered / count($this->questions)) * 100);

            $this->surveyResponse->update(['progress' => $progress]);

            // Dispatch para actualizar la UI
            $this->dispatch('answerSaved', ['questionId' => $questionId, 'value' => $value]);
        } catch (\Exception $e) {
            // Log del error si es necesario
            logger('Error saving answer: ' . $e->getMessage());
        }
    }

    public function completeSurvey()
    {
        $this->saveCurrentAnswer();

        // Marcar como completado
        $this->surveyResponse->update([
            'completed_at' => now(),
            'progress' => 100,
        ]);

        // Actualizar lista de completados
        $this->completedAssignments[] = $this->currentAssignment->id;

        // Verificar si se completaron todas las asignaturas
        if (count($this->completedAssignments) === $this->assignments->count()) {
            $this->currentStep = 'all_completed';
        } else {
            $this->currentStep = 'subjects';
            session()->flash('success', 'Evaluación completada correctamente');
        }
    }

    public function backToSubjects()
    {
        $this->currentStep = 'subjects';
    }

    public function render()
    {
        return view('livewire.public-survey-form')
            ->layout('layouts.public');
    }
}
