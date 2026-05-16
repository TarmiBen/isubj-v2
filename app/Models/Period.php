<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use SoftDeletes;
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
