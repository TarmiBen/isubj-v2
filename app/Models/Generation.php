<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Generation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'career_id',
        'number',
        'start_date',
        'end_date',
    ];


    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('generation')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
