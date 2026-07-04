<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Qualification extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('qualification')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
     * Recalcula la calificación final del estudiante para el assignment.
     * Delega en FinalGrade::recalculateForAssignment(), que es la lógica
     * real usada en toda la app (respeta la config de tipo práctico/teórico
     * por unidad y usa assignment_id, no subject_id).
     */
    protected function recalculateFinalGrade()
    {
        if (!$this->unity || !$this->unity->assignment) {
            return;
        }

        FinalGrade::recalculateForAssignment($this->unity->assignment->id);
    }
}
