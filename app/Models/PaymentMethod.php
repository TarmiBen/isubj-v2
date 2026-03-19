<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'requires_reference',
        'requires_bank_info',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'requires_reference' => 'boolean',
            'requires_bank_info' => 'boolean',
            'active'             => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}