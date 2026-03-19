<?php

namespace App\Observers;

use App\Models\Qualification;
use App\Models\FinalGrade;

class QualificationObserver
{
    /**
     * Handle the Qualification "created" event.
     */
    public function created(Qualification $qualification): void
    {
        $this->recalculateFinalGrades($qualification);
    }

    /**
     * Handle the Qualification "updated" event.
     */
    public function updated(Qualification $qualification): void
    {
        $this->recalculateFinalGrades($qualification);
    }

    /**
     * Handle the Qualification "deleted" event.
     */
    public function deleted(Qualification $qualification): void
    {
        $this->recalculateFinalGrades($qualification);
    }

    /**
     * Recalcula las calificaciones finales cuando se modifica una calificación parcial
     */
    private function recalculateFinalGrades(Qualification $qualification): void
    {
        try {
            // Obtener el assignment relacionado a través de la unit
            $unit = $qualification->unity;
            if ($unit && $unit->assignment_id) {
                FinalGrade::recalculateForAssignment($unit->assignment_id);
            }
        } catch (\Exception $e) {
            \Log::error('Error recalculando calificaciones finales desde Observer: ' . $e->getMessage());
        }
    }
}
