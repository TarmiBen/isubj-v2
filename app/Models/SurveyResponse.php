<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_related_id',
        'student_id',
        'cycle_id',
        'completed_at',
        'progress',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'progress' => 'integer',
    ];

    public function surveyRelated(): BelongsTo
    {
        return $this->belongsTo(SurveyRelated::class, 'survey_related_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('completed_at');
    }
}
