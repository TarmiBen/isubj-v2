<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'survey_id',
        'question',
        'type',
        'required',
        'order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'order' => 'integer',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'survey_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'survey_question_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
