<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Qualification;
use App\Models\Unit;

class UnitStatusController extends Controller
{
    /**
     * Vista pública (sin autenticación) que muestra únicamente el estatus
     * de carga de calificaciones de una unidad. No expone información
     * confidencial (nombres de alumnos, calificaciones, etc.).
     */
    public function show(string $uuid)
    {
        $unit = Unit::with([
            'assignment.subject',
            'assignment.group.period.career',
            'assignment.cycle',
        ])->where('uuid', $uuid)->firstOrFail();

        $assignment = $unit->assignment;
        $group = $assignment->group;

        $activeStudentsCount = $group->inscriptions()
            ->where('status', 'active')
            ->whereHas('student', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $loadedQualificationsCount = Qualification::where('unity_id', $unit->id)
            ->where('score', '>', 0)
            ->count();

        if (empty($unit->document_src)) {
            $status = 'no_document';
            $message = 'No hay documento ni calificaciones cargadas para esta unidad.';
        } elseif ($activeStudentsCount === 0) {
            $status = 'no_students';
            $message = 'No hay alumnos activos registrados para esta unidad.';
        } elseif ($loadedQualificationsCount === 0) {
            $status = 'no_grades';
            $message = 'No hay calificaciones cargadas para esta unidad.';
        } elseif ($loadedQualificationsCount < $activeStudentsCount) {
            $faltantes = $activeStudentsCount - $loadedQualificationsCount;
            $status = 'partial';
            $message = $faltantes === 1
                ? 'Falta 1 calificación de alumno por cargar.'
                : "Faltan {$faltantes} calificaciones de alumnos por cargar.";
        } else {
            $status = 'complete';
            $message = 'CALIFICACIONES CARGADAS CORRECTAMENTE';
        }

        $currentCycle = Cycle::active()->current()->first();
        $isCurrentCycle = $currentCycle && $assignment->cycle_id === $currentCycle->id;

        return view('public.unit-status', [
            'unit' => $unit,
            'assignment' => $assignment,
            'status' => $status,
            'message' => $message,
            'isCurrentCycle' => $isCurrentCycle,
            'currentCycle' => $currentCycle,
        ]);
    }
}