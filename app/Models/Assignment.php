<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory; // LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'teacher_id',
        'subject_id',
        'cycle_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'group_id' => 'integer',
            'teacher_id' => 'integer',
            'subject_id' => 'integer',
        ];
    }

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly(['group_id', 'teacher_id', 'subject_id']) // campos a logear
    //         ->useLogName('Asignament')
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function surveyRelated()
    {
        return $this->morphMany(SurveyRelated::class, 'survivable');
    }

    public function finalGrades()
    {
        return $this->hasMany(FinalGrade::class);
    }

}
