<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lows extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'comment',
        'inscription_id',
        'date',
        'type',
        'incripion_id',
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
            'date' => 'date',
            'incripion_id' => 'integer',
        ];
    }

    public function incripion(): BelongsTo
    {
        return $this->belongsTo(Incripion::class);
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }
}
