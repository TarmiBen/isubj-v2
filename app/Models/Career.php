<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    protected $fillable = [
        'code',
        'name',
        'abbreviation',
        'description',
        'duration_id',
        'duration_time',
        'total_credits',
        'modality_id',
        'coordinator_id',
        'status',
    ];

    protected $casts = [
        'duration_id' => 'integer',
        'total_credits' => 'integer',
        'modality_id' => 'integer',
        'coordinator_id' => 'integer',
    ];

    public function duration()
    {
        return $this->belongsTo(Duration::class);
    }
    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }
    public function subject()
    {
        return $this->hasMany(Subject::class);
    }
    public function generations()
    {
        return $this->hasMany(Generation::class);
    }


}
