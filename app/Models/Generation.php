<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generation extends Model
{
    use HasFactory;

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
}
