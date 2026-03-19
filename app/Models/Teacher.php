<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Teacher extends Model
{
    use HasFactory;
    use softDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name1',
        'last_name2',
        'gender',
        'date_of_birth',
        'curp',
        'email',
        'phone',
        'mobile',
        'hire_date',
        'status',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'title',
        'specialization',
        'photo',
        'photo_thumb',
        'emergency_contact_name',
        'emergency_contact_phone',
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
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'meta' => 'array',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'assignments')->distinct();
    }


    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name1 . ' ' . $this->last_name2);
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name1 . ' ' . $this->last_name2);
    }


    public function qualifications()
    {
        return $this->hasMany(Qualification::class, 'teacher_id');
    }

    public function surveyRelated()
    {
        return $this->morphMany(SurveyRelated::class, 'survivable');
    }
}
