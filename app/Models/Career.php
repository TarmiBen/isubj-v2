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
}
