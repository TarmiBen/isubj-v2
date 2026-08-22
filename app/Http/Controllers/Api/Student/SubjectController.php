<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\FinalGrade;
use App\Models\Qualification;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Materias de la última inscripción ACTIVA del alumno, con su calificación
     * final (si ya existe) y un resumen/promedio arriba.
     */
    public function index(Request $request)
    {
        $student = $request->student();
        $inscription = $this->activeInscription($student);

        if (! $inscription) {
            return response()->json([
                'cycle' => null,
                'group' => null,
                'average' => null,
                'subjects' => [],
            ]);
        }

        $assignments = $inscription->group->assignments()->with(['subject', 'teacher'])->get();

        $finalGrades = FinalGrade::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->orderByDesc('attempt')
            ->get()
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $subjects = $assignments->map(function (Assignment $assignment) use ($finalGrades) {
            $grade = $finalGrades->get($assignment->id);

            return [
                'assignment_id' => $assignment->id,
                'subject_name' => $assignment->subject?->name,
                'subject_code' => $assignment->subject?->code,
                'teacher_name' => $this->teacherName($assignment),
                'final_grade' => $grade?->grade !== null ? (float) $grade->grade : null,
                'status' => $grade?->status,
            ];
        })->values();

        $withGrade = $subjects->filter(fn ($s) => $s['final_grade'] !== null);

        return response()->json([
            'cycle' => $inscription->cycle_id ? $inscription->cycle?->name : null,
            'group' => $inscription->group->code,
            'average' => $withGrade->isNotEmpty() ? round($withGrade->avg('final_grade'), 2) : null,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Desglose por unidad de una materia, solo si pertenece a la inscripción
     * activa del alumno autenticado.
     */
    public function show(Request $request, Assignment $assignment)
    {
        $student = $request->student();
        $inscription = $this->activeInscription($student);

        if (! $inscription || $assignment->group_id !== $inscription->group_id) {
            abort(404);
        }

        $assignment->load(['subject', 'teacher', 'units']);

        $qualifications = Qualification::where('student_id', $student->id)
            ->whereIn('unity_id', $assignment->units->pluck('id'))
            ->get()
            ->keyBy('unity_id');

        $finalGrade = FinalGrade::where('student_id', $student->id)
            ->where('assignment_id', $assignment->id)
            ->orderByDesc('attempt')
            ->first();

        $units = $assignment->units->map(function ($unit) use ($qualifications) {
            $q = $qualifications->get($unit->id);

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'score' => $q?->score !== null ? (float) $q->score : null,
                'comments' => $q?->comments,
            ];
        })->values();

        return response()->json([
            'subject_name' => $assignment->subject?->name,
            'subject_code' => $assignment->subject?->code,
            'teacher_name' => $this->teacherName($assignment),
            'final_grade' => $finalGrade?->grade !== null ? (float) $finalGrade->grade : null,
            'status' => $finalGrade?->status,
            'units' => $units,
        ]);
    }

    private function activeInscription($student)
    {
        return $student->inscriptions()
            ->where('status', 'active')
            ->with('group', 'cycle')
            ->latest()
            ->first();
    }

    private function teacherName(Assignment $assignment): ?string
    {
        $teacher = $assignment->teacher;

        if (! $teacher) {
            return null;
        }

        return trim("{$teacher->first_name} {$teacher->last_name1} {$teacher->last_name2}");
    }
}
