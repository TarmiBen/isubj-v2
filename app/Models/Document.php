<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Document extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'src', 'meta'];
    protected $casts = [
        'meta' => 'array',
    ];
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function documentable()
    {
        return $this->morphTo();
    }

    public function getActivityLogOptions(): LogOptions
    {
        return logOptions::defaults()
            ->logAll()
            ->useLogName(Document::class)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
