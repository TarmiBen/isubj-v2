<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;


class FinalGrade extends Model
{
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
     * Calcula y guarda la calificación final de un estudiante en un assignment
     */
    public static function calculateAndSave(int $studentId, int $assignmentId, array $calculatedFrom = []): ?self
    {
        // Obtener las calificaciones parciales del estudiante para este assignment
        $qualifications = \App\Models\Qualification::where('student_id', $studentId)
            ->whereHas('unity', function($query) use ($assignmentId) {
                $query->where('assignment_id', $assignmentId);
            })
            ->where('score', '>', 0)
            ->get();

        if ($qualifications->isEmpty()) {
            return null;
        }

        // Calcular promedio
        $average = $qualifications->avg('score');
        $finalGrade = round($average, 1); // Las calificaciones ya están en escala 0-10

        // Determinar el siguiente intento
        $lastAttempt = self::where('student_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->max('attempt') ?? 0;

        $nextAttempt = $lastAttempt + 1;

        // No permitir más de 3 intentos
        if ($nextAttempt > 3) {
            return null;
        }

        // Determinar status y source
        $status = $finalGrade >= 7.0 ? 'passed' : 'failed';
        $source = match($nextAttempt) {
            1 => 'ordinario',
            2 => 'extraordinario',
            3 => 'especial',
            default => 'ordinario'
        };

        return self::create([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
            'attempt' => $nextAttempt,
            'grade' => $finalGrade,
            'status' => $status,
            'source' => $source,
            'calculated_from' => $calculatedFrom ?: $qualifications->pluck('id')->toArray()
        ]);
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
        $this->grade = round($newGrade, 1);
        $this->status = $this->grade >= 7.0 ? 'passed' : 'failed';

        return $this->save();
    }

    /**
     * Calcula automáticamente la calificación final de un estudiante específico si tiene todas las unidades completas
     */
    public static function autoCalculateForStudent(int $studentId, int $assignmentId): ?self
    {
        try {
            $assignment = \App\Models\Assignment::with(['units'])->find($assignmentId);

            if (!$assignment) {
                return null;
            }

            $totalUnits = $assignment->units->count();
            $subjectId = $assignment->subject_id;
            $unitIds = $assignment->units->pluck('id');

            // Obtener calificaciones del estudiante
            $qualifications = \App\Models\Qualification::where('student_id', $studentId)
                ->whereIn('unity_id', $unitIds)
                ->where('score', '>', 0)
                ->get();

            // Solo proceder si tiene todas las unidades calificadas
            if ($qualifications->count() < $totalUnits) {
                return null;
            }

            // Verificar si ya existe una calificación final automática
            $existingFinalGrade = self::where('student_id', $studentId)
                ->where('assignment_id', $assignmentId)
                ->where('attempt', 1)
                ->first();

            // Verificar si hay configuración de tipos de unidades
            $hasConfiguration = $assignment->units->contains(function ($unit) {
                return isset($unit->meta['tipo']);
            });

            // Calcular el promedio según la configuración
            $finalGrade = 0;

            if ($hasConfiguration) {
                // Separar unidades por tipo
                $practicoUnits = collect();
                $teoricoUnits = collect();

                foreach ($assignment->units as $unit) {
                    $tipo = $unit->meta['tipo'] ?? 'teorico';
                    if ($tipo === 'practico') {
                        $practicoUnits->push($unit->id);
                    } else {
                        $teoricoUnits->push($unit->id);
                    }
                }

                // Calcular promedio de unidades prácticas
                $promedioPractico = null;
                if ($practicoUnits->isNotEmpty()) {
                    $practicoQualifications = $qualifications->whereIn('unity_id', $practicoUnits->toArray());
                    if ($practicoQualifications->isNotEmpty()) {
                        $promedioPractico = $practicoQualifications->avg('score');
                    }
                }

                // Calcular promedio de unidades teóricas
                $promedioTeorico = null;
                if ($teoricoUnits->isNotEmpty()) {
                    $teoricoQualifications = $qualifications->whereIn('unity_id', $teoricoUnits->toArray());
                    if ($teoricoQualifications->isNotEmpty()) {
                        $promedioTeorico = $teoricoQualifications->avg('score');
                    }
                }

                // Calcular promedio final entre práctico y teórico
                if ($promedioPractico !== null && $promedioTeorico !== null) {
                    $finalGrade = round(($promedioPractico + $promedioTeorico) / 2, 1);
                } elseif ($promedioPractico !== null) {
                    $finalGrade = round($promedioPractico, 1);
                } elseif ($promedioTeorico !== null) {
                    $finalGrade = round($promedioTeorico, 1);
                } else {
                    $finalGrade = 0;
                }
            } else {
                // Lógica original: promedio simple
                $average = $qualifications->avg('score');
                $finalGrade = round($average, 1);
            }

            if ($existingFinalGrade) {
                // Actualizar la calificación existente
                $existingFinalGrade->update([
                    'grade' => $finalGrade,
                    'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                    'calculated_from' => $qualifications->pluck('id')->toArray()
                ]);
                return $existingFinalGrade;
            } else {
                // Crear nueva calificación final automática
                return self::create([
                    'student_id' => $studentId,
                    'assignment_id' => $assignmentId,
                    'attempt' => 1,
                    'grade' => $finalGrade,
                    'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                    'source' => 'ordinario',
                    'calculated_from' => $qualifications->pluck('id')->toArray()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Error calculando calificación final automática para estudiante {$studentId}: " . $e->getMessage());
            return null;
        }
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

            // Verificar si hay configuración de tipos de unidades
            $hasConfiguration = $assignment->units->contains(function ($unit) {
                return isset($unit->meta['tipo']);
            });

            // Separar unidades por tipo si hay configuración
            $practicoUnits = collect();
            $teoricoUnits = collect();

            if ($hasConfiguration) {
                foreach ($assignment->units as $unit) {
                    $tipo = $unit->meta['tipo'] ?? 'teorico';
                    if ($tipo === 'practico') {
                        $practicoUnits->push($unit->id);
                    } else {
                        $teoricoUnits->push($unit->id);
                    }
                }
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
                    $deletedCount = self::where('student_id', $student->id)
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

                // Calcular el promedio según la configuración
                $finalGrade = 0;

                if ($hasConfiguration && ($practicoUnits->isNotEmpty() || $teoricoUnits->isNotEmpty())) {
                    // Nueva lógica: calcular promedio separado para práctico y teórico
                    $promedioPractico = null;
                    $promedioTeorico = null;

                    // Calcular promedio de unidades prácticas
                    if ($practicoUnits->isNotEmpty()) {
                        $practicoQualifications = $qualifications->whereIn('unity_id', $practicoUnits->toArray());
                        if ($practicoQualifications->isNotEmpty()) {
                            $promedioPractico = $practicoQualifications->avg('score');
                        }
                    }

                    // Calcular promedio de unidades teóricas
                    if ($teoricoUnits->isNotEmpty()) {
                        $teoricoQualifications = $qualifications->whereIn('unity_id', $teoricoUnits->toArray());
                        if ($teoricoQualifications->isNotEmpty()) {
                            $promedioTeorico = $teoricoQualifications->avg('score');
                        }
                    }

                    // Calcular promedio final entre práctico y teórico
                    if ($promedioPractico !== null && $promedioTeorico !== null) {
                        // Hay ambos tipos: promediar entre ellos
                        $finalGrade = round(($promedioPractico + $promedioTeorico) / 2, 1);
                    } elseif ($promedioPractico !== null) {
                        // Solo hay prácticas
                        $finalGrade = round($promedioPractico, 1);
                    } elseif ($promedioTeorico !== null) {
                        // Solo hay teóricas
                        $finalGrade = round($promedioTeorico, 1);
                    } else {
                        // No hay calificaciones válidas
                        $finalGrade = 0;
                    }
                } else {
                    // Lógica original: promedio simple de todas las unidades
                    $average = $qualifications->avg('score');
                    $finalGrade = round($average, 1);
                }

                if ($existingFinalGrade) {
                    $existingFinalGrade->update([
                        'grade' => $finalGrade,
                        'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                        'calculated_from' => $qualifications->pluck('id')->toArray()
                    ]);
                    $updatedCount++;
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
