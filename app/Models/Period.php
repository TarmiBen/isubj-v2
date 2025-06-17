<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'id',
        'career_id',
        'name',
        'number',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

}
