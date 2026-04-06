<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory; // LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'career_id',
        'credits',
        'period_id',
        'status',
        'meta'
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
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
    public function career(){

        return $this->belongsTo(Career::class);
    }

    public function period(){
        return $this->belongsTo(Period::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // public function getActivitylogOptions() :LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logAll()
    //         ->useLogName('subject')
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }
}
