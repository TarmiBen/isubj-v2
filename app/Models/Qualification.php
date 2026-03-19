<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qualification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'unity_id',
        'score',
        'comments'
    ];

    protected static function booted()
    {
        static::created(function ($qualification) {
            $qualification->recalculateFinalGrade();
        });

        static::updated(function ($qualification) {
            $qualification->recalculateFinalGrade();
        });

        static::deleted(function ($qualification) {
            $qualification->recalculateFinalGrade();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function unity(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unity_id');
    }

    /**
     * Recalcula la calificación final del estudiante para la materia
     */
    protected function recalculateFinalGrade()
    {
        try {
            if (!$this->unity || !$this->unity->assignment) {
                return;
            }

            $subjectId = $this->unity->assignment->subject_id;
            $studentId = $this->student_id;

            // Obtener todas las unidades de la materia del assignment específico
            $assignment = $this->unity->assignment;
            $totalUnits = Unit::where('assignment_id', $assignment->id)->count();

            // Obtener calificaciones del estudiante para este assignment específico
            $qualifications = self::where('student_id', $studentId)
                ->whereIn('unity_id', Unit::where('assignment_id', $assignment->id)->pluck('id'))
                ->where('score', '>', 0)
                ->get();

            // Si no tiene todas las unidades calificadas, eliminar calificación final si existe
            if ($qualifications->count() < $totalUnits) {
                FinalGrade::where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('attempt', 1) // Solo eliminar el primer intento (automático)
                    ->delete();
                return;
            }

            // Verificar si ya existe una calificación final (primer intento)
            $existingFinalGrade = FinalGrade::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('attempt', 1)
                ->first();

            // Calcular el promedio
            $average = $qualifications->avg('score');
            $finalGrade = round($average / 10, 1); // Convertir de 0-100 a 0-10

            if ($existingFinalGrade) {
                // Actualizar la calificación existente (primer intento)
                $existingFinalGrade->grade = $finalGrade;
                $existingFinalGrade->status = $finalGrade >= 7.0 ? 'passed' : 'failed';
                $existingFinalGrade->calculated_from = $qualifications->pluck('id')->toArray();
                $existingFinalGrade->save();
            } else {
                // Crear nueva calificación final (primer intento solamente)
                FinalGrade::create([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'attempt' => 1,
                    'grade' => $finalGrade,
                    'status' => $finalGrade >= 7.0 ? 'passed' : 'failed',
                    'source' => 'ordinario',
                    'calculated_from' => $qualifications->pluck('id')->toArray()
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::error('Error recalculando calificación final: ' . $e->getMessage());
        }
    }
}
