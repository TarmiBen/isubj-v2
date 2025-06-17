<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPractice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inscription_id',
        'practice_id',
        'practice_type_id',
        'scenario',
        'scheduled_at',
        'completed_at',
        'status',
        'instructor_id',
        'result',
        'observations',
        'meta',
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
            'inscription_id' => 'integer',
            'practice_id' => 'integer',
            'practice_type_id' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'instructor_id' => 'integer',
            'meta' => 'array',
        ];
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
