<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $fillable = [
        'name',
        'code',
        'starts_at',
        'ends_at',
        'active',
        'description',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'active' => 'boolean',
    ];

    public function surveyRelated(): HasMany
    {
        return $this->hasMany(SurveyRelated::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
                    ->where('active', true);
    }
}
