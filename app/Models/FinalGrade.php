<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class FinalGrade extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('final_grade')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected $table = 'final_grades';

    protected $fillable = [
        'student_id',
        'assignment_id',
        'attempt',
        'grade',
        'status',
        'source',
        'calculated_from'
    ];

    protected $casts = [
        'grade'           => 'decimal:2',
        'attempt'         => 'integer',
        'calculated_from' => 'array',
    ];

    /* =======================
     | Relationships
     ======================= */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment()
    {
        return $this->belongsTo(\App\Models\Assignment::class);
    }

    /* =======================
     | Scopes
     ======================= */

    public function scopePassed(Builder $query): Builder
    {
        return $query->where('status', 'passed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeLatestAttempt(Builder $query): Builder
    {
        return $query->orderByDesc('attempt');
    }

    /* =======================
     | Helpers
     ======================= */

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isExtraordinary(): bool
    {
        return $this->attempt > 1;
    }

    public function isFailed(): bool
    {
        return $this->grade < 7.0;
    }

    public function getAttemptTypeAttribute(): string
    {
        return match($this->attempt) {
            1 => '', // Primer intento no muestra nada
            2 => 'E.E', // Extraordinario
            3 => 'T.S', // Título de suficiencia
            default => ''
        };
    }

    public function getGradeColorClass(): string
    {
        return $this->isFailed() ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
    }

    public function getGradeBgColorClass(): string
    {
        return $this->isFailed()
            ? 'bg-red-100 dark:bg-red-900 border-red-300 dark:border-red-600'
            : 'bg-green-100 dark:bg-green-900 border-green-300 dark:border-green-600';
    }

    /**
     * Redondea una calificación a 1 decimal con regla asimétrica:
     * - Si el resultado queda por debajo de 7, el segundo decimal siempre se trunca hacia abajo
     *   (6.95 -> 6.9, 6.99 -> 6.9, 5.98 -> 5.9).
     * - Si el resultado queda en 7 o más, el segundo decimal siempre redondea hacia arriba
     *   (7.05 -> 7.1, 7.09 -> 7.1).
     */
    public static function roundFinalGrade(float $grade): float
    {
        $rounded2 = round($grade, 2);
        $formatted = number_format($rounded2, 2, '.', '');
        [$intPart, $decPart] = explode('.', $formatted);

        $firstDecimal = $decPart[0];
        $secondDecimal = (int) $decPart[1];

        $base = (float) "{$intPart}.{$firstDecimal}";

        if ($secondDecimal === 0) {
            return $base;
        }

        if ($rounded2 < 7.0) {
            return $base;
        }

        return round($base + 0.1, 1);
    }

    /**
     * Obtiene la última calificación final de un estudiante en un assignment
     */
    public static function getLatestGrade(int $studentId, int $assignmentId): ?self
    {
        return self::where('student_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->orderByDesc('attempt')
            ->first();
    }

    /**
     * Verifica si un estudiante puede tener más intentos
     */
    public static function canHaveMoreAttempts(int $studentId, int $assignmentId): bool
    {
        $lastGrade = self::getLatestGrade($studentId, $assignmentId);

        if (!$lastGrade) {
            return true; // Primer intento
        }

        // Si pasó, no necesita más intentos
        if ($lastGrade->isPassed()) {
            return false;
        }

        // Máximo 3 intentos
        return $lastGrade->attempt < 3;
    }

    /**
     * Actualiza una calificación final existente
     */
    public function updateGrade(float $newGrade): bool
    {
        $this->grade = self::roundFinalGrade($newGrade);
        $this->status = $this->grade >= 7.0 ? 'passed' : 'failed';

        return $this->save();
    }

    /**
     * Recalcula automáticamente las calificaciones finales para todos los estudiantes de un assignment
     */
    public static function recalculateForAssignment(int $assignmentId): void
    {
        try {
            $assignment = \App\Models\Assignment::with(['units', 'group.students'])->find($assignmentId);

            if (!$assignment) {
                \Log::warning("Assignment ID {$assignmentId} no encontrado");
                return;
            }

            $totalUnits = $assignment->units->count();
            $unitIds = $assignment->units->pluck('id');
            $students = $assignment->group->students ?? collect();

            if ($totalUnits === 0 || $students->isEmpty()) {
                return;
            }

            $processedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;

            foreach ($students as $student) {
                // Obtener calificaciones del estudiante para este assignment
                $qualifications = \App\Models\Qualification::where('student_id', $student->id)
                    ->whereIn('unity_id', $unitIds)
                    ->where('score', '>', 0)
                    ->get();

                // Si no tiene todas las unidades calificadas, eliminar calificación final automática
                if ($qualifications->count() < $totalUnits) {
                    self::where('student_id', $student->id)
                        ->where('assignment_id', $assignmentId)
                        ->where('attempt', 1)
                        ->delete();
                    continue;
                }

                // Verificar si ya existe una calificación final automática
                $existingFinalGrade = self::where('student_id', $student->id)
                    ->where('assignment_id', $assignmentId)
                    ->where('attempt', 1)
                    ->first();

                // Promedio simple de todas las unidades, sin distinguir tipo práctico/teórico
                $average = $qualifications->avg('score');
                $finalGrade = self::roundFinalGrade($average);

                if ($existingFinalGrade) {
                    // Solo escribir si la calificación realmente cambió, para no generar
                    // actualizaciones/log de actividad innecesarios cada vez que se entra a la materia
                    $gradeChanged = number_format((float) $existingFinalGrade->grade, 1) !== number_format($finalGrade, 1);

                    if ($gradeChanged) {
                        $existingFinalGrade->update([
                            'grade' => $finalGrade,
                            'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                            'calculated_from' => $qualifications->pluck('id')->toArray()
                        ]);
                        $updatedCount++;
                    }
                } else {
                    self::create([
                        'student_id' => $student->id,
                        'assignment_id' => $assignmentId,
                        'attempt' => 1,
                        'grade' => $finalGrade,
                        'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                        'source' => 'ordinario',
                        'calculated_from' => $qualifications->pluck('id')->toArray()
                    ]);
                    $createdCount++;
                }
                $processedCount++;
            }

            \Log::info("Recálculo completado para assignment {$assignmentId}: {$processedCount} procesados, {$createdCount} creados, {$updatedCount} actualizados");

        } catch (\Exception $e) {
            \Log::error('Error recalculando calificaciones finales del assignment: ' . $e->getMessage());
        }
    }
}
