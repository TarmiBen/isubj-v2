<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Document extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'src', 'meta', 'documentable_type', 'documentable_id'];

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
