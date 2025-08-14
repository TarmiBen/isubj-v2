<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Document;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Inscription;

class Student extends Model
{
    use HasFactory, LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_number',
        'name',
        'last_name1',
        'last_name2',
        'gender',
        'date_of_birth',
        'curp',
        'email',
        'phone',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'enrollment_date',
        'status',
        'guardian_name',
        'guardian_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'photo',
        'code',
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
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
        ];
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }


    public function getActivitylogOptions() : LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('student')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->last_name1} {$this->last_name2}";
    }

    public function getLastInscriptionAttribute()
    {
        return $this->inscriptions()->with('group')->latest()->first();
    }




}
