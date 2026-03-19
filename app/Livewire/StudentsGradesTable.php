<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Unit;
use App\Models\Qualification;
use App\Models\FinalGrade;
use Illuminate\Support\Facades\Auth;

class StudentsGradesTable extends Component
{
    public $assignment;
    public $students;
    public $units;
    public $credits;

    // Properties for the modal
    public $showModal = false;
    public $selectedStudent;
    public $grades = [];
    public $comments = [];


    public $showFinalGradeModal = false;
    public $selectedStudentForFinal;
    public $finalGrade = '';
    public $isEditingFinalGrade = false;
    public $editingFinalGradeId = null;

    protected $rules = [
        'grades.*' => 'nullable|numeric|between:0,10',
        'comments.*' => 'nullable|string|max:500',
        'finalGrade' => 'nullable|required|numeric|between:0,10'
    ];

    protected $messages = [
        'grades.*.numeric' => 'La calificación debe ser un número válido',
        'grades.*.between' => 'La calificación debe estar entre 0 y 100',
        'comments.*.max' => 'El comentario no puede exceder los 500 caracteres',
        'finalGrade.required' => 'La calificación final es requerida',
        'finalGrade.numeric' => 'La calificación final debe ser un número válido',
        'finalGrade.between' => 'La calificación final debe estar entre 0 y 10'
    ];

    public function mount($assignment, $students, $units, $credits = null)
    {
        $this->assignment = $assignment;
        $this->students = $students;
        $this->units = $units;
        $this->credits = $credits;

        // Recalcular automáticamente las calificaciones finales al cargar
        $this->recalculateAllFinalGrades();
    }


    public function recalculateAllFinalGrades()
    {
        if ($this->assignment && $this->assignment->id) {
            try {
                FinalGrade::recalculateForAssignment($this->assignment->id);
            } catch (\Exception $e) {
                \Log::error('Error en recálculo automático: ' . $e->getMessage());
            }
        }
    }

    public function openGradesModal($studentId)
    {
        try {
            $this->selectedStudent = Student::findOrFail($studentId);
            $this->loadExistingGrades();
            $this->showModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al abrir el modal: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedStudent = null;
        $this->grades = [];
        $this->comments = [];
    }

    public function loadExistingGrades()
    {
        $this->grades = [];
        $this->comments = [];

        // Optimización: cargar todas las calificaciones de una vez
        $qualifications = Qualification::where('student_id', $this->selectedStudent->id)
            ->whereIn('unity_id', $this->units->pluck('id'))
            ->get()
            ->keyBy('unity_id');

        foreach ($this->units as $unit) {
            $qualification = $qualifications->get($unit->id);

            if ($qualification) {
                $this->grades[$unit->id] = $qualification->score;
                $this->comments[$unit->id] = $qualification->comments;
            } else {
                $this->grades[$unit->id] = null;
                $this->comments[$unit->id] = null;
            }
        }
    }

    public function saveGrades()
    {


        // Validar solo las calificaciones que no sean null
        $gradesToValidate = array_filter($this->grades ?? [], function($grade) {
            return $grade !== null && $grade !== '';
        });

        foreach ($gradesToValidate as $unitId => $grade) {
            if (!is_numeric($grade) || $grade < 0 || $grade > 10) {
                session()->flash('error', 'Las calificaciones deben ser números entre 0 y 10.');
                return;
            }
        }

        $savedGrades = 0;

        try {
            foreach ($this->units as $unit) {
                if ($this->grades[$unit->id] !== null || $this->comments[$unit->id] !== null) {
                    Qualification::updateOrCreate(
                        [
                            'student_id' => $this->selectedStudent->id,
                            'unity_id' => $unit->id,
                        ],
                        [
                            'teacher_id' => Auth::user()->teacher?->id ?? 1,
                            'score' => $this->grades[$unit->id] ?? 0,
                            'comments' => $this->comments[$unit->id],
                        ]
                    );
                    $savedGrades++;
                }
            }

            // Recalcular automáticamente las calificaciones finales después de guardar
            if ($this->assignment && $this->assignment->id) {
                FinalGrade::recalculateForAssignment($this->assignment->id);
            }

            $this->closeModal();

            // Mostrar mensaje de éxito
            session()->flash('success', "Se guardaron {$savedGrades} calificaciones exitosamente para {$this->selectedStudent->name}.");

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar las calificaciones: ' . $e->getMessage());
        }
    }

    // Métodos para editar calificaciones finales existentes
    public function openEditFinalGradeModal($finalGradeId)
    {
        try {
            $finalGrade = FinalGrade::findOrFail($finalGradeId);
            $this->selectedStudentForFinal = $finalGrade->student;
            $this->finalGrade = $finalGrade->grade;
            $this->editingFinalGradeId = $finalGradeId;
            $this->isEditingFinalGrade = true;
            $this->showFinalGradeModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al abrir el modal de edición: ' . $e->getMessage());
        }
    }

    public function closeFinalGradeModal()
    {
        $this->showFinalGradeModal = false;
        $this->selectedStudentForFinal = null;
        $this->finalGrade = '';
        $this->isEditingFinalGrade = false;
        $this->editingFinalGradeId = null;
    }

    public function saveFinalGrade()
    {
        $this->validate([
            'finalGrade' => 'required|numeric|between:0,10'
        ]);

        try {
            // Solo actualizar calificación existente (no crear nuevas)
            $finalGrade = FinalGrade::findOrFail($this->editingFinalGradeId);
            $finalGrade->updateGrade($this->finalGrade);

            session()->flash('success', 'Calificación final actualizada correctamente.');
            $this->closeFinalGradeModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la calificación final: ' . $e->getMessage());
        }
    }

    public function openNewAttemptModal($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);

            // Verificar si el estudiante puede tener más intentos
            if (!FinalGrade::canHaveMoreAttempts($studentId, $this->assignment->id)) {
                session()->flash('error', 'Este estudiante ya no puede tener más intentos o ya aprobó la materia.');
                return;
            }

            $this->selectedStudentForFinal = $student;
            $this->finalGrade = '';
            $this->isEditingFinalGrade = false;
            $this->showFinalGradeModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al abrir el modal: ' . $e->getMessage());
        }
    }

    public function saveNewAttempt()
    {
        $this->validate([
            'finalGrade' => 'required|numeric|between:0,10'
        ]);

        try {
            // Determinar el siguiente intento
            $lastAttempt = FinalGrade::where('student_id', $this->selectedStudentForFinal->id)
                ->where('assignment_id', $this->assignment->id)
                ->max('attempt') ?? 0;

            $nextAttempt = $lastAttempt + 1;

            if ($nextAttempt > 3) {
                session()->flash('error', 'No se pueden registrar más de 3 intentos.');
                return;
            }

            // Determinar status y source
            $status = $this->finalGrade >= 7.0 ? 'passed' : 'failed';
            $source = match($nextAttempt) {
                1 => 'ordinario',
                2 => 'extraordinario',
                3 => 'especial',
                default => 'ordinario'
            };

            FinalGrade::create([
                'student_id' => $this->selectedStudentForFinal->id,
                'assignment_id' => $this->assignment->id,
                'attempt' => $nextAttempt,
                'grade' => $this->finalGrade,
                'status' => $status,
                'source' => $source,
                'calculated_from' => [] // Manual para intentos adicionales
            ]);

            session()->flash('success', 'Nuevo intento registrado correctamente.');
            $this->closeFinalGradeModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al registrar el nuevo intento: ' . $e->getMessage());
        }
    }

    public function refreshFinalGrades()
    {
        try {
            if ($this->assignment && $this->assignment->id) {
                FinalGrade::recalculateForAssignment($this->assignment->id);
                session()->flash('success', 'Calificaciones finales recalculadas correctamente.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al recalcular calificaciones finales: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.students-grades-table');
    }
}

