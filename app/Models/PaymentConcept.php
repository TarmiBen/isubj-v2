<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentConcept extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'default_amount',
        'is_periodic',
        'period_type',
        'is_taxable',
        'tax_rate',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_periodic'    => 'boolean',
            'is_taxable'     => 'boolean',
            'tax_rate'       => 'decimal:2',
            'active'         => 'boolean',
        ];
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function monthlyFeeConfigs(): HasMany
    {
        return $this->hasMany(MonthlyFeeConfig::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}