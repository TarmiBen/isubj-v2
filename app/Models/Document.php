<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Document extends Model
{
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
}
