<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Career extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Career')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();

    }

}
