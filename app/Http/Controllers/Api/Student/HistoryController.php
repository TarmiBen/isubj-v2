<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\FinalGrade;
use App\Models\Inscription;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /** Ciclos cursados por el alumno (todas sus inscripciones, más recientes primero). */
    public function index(Request $request)
    {
        $student = $request->student();

        $inscriptions = $student->inscriptions()
            ->with('cycle', 'group')
            ->latest()
            ->get();

        return response()->json($inscriptions->map(fn (Inscription $i) => [
            'inscription_id' => $i->id,
            'cycle' => $i->cycle?->name,
            'group' => $i->group?->code,
            'status' => $i->status,
        ]));
    }

    /**
     * Solo calificación final de cada materia del ciclo — sin desglose por
     * unidad, a diferencia de la inscripción activa actual.
     */
    public function show(Request $request, Inscription $inscription)
    {
        $student = $request->student();

        if ($inscription->student_id !== $student->id) {
            abort(404);
        }

        $assignments = $inscription->group->assignments()->with(['subject', 'teacher'])->get();

        $finalGrades = FinalGrade::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->orderByDesc('attempt')
            ->get()
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $subjects = $assignments->map(function ($assignment) use ($finalGrades) {
            $grade = $finalGrades->get($assignment->id);

            return [
                'subject_name' => $assignment->subject?->name,
                'final_grade' => $grade?->grade !== null ? (float) $grade->grade : null,
                'status' => $grade?->status,
            ];
        })->values();

        return response()->json([
            'cycle' => $inscription->cycle?->name,
            'group' => $inscription->group->code,
            'subjects' => $subjects,
        ]);
    }
}
