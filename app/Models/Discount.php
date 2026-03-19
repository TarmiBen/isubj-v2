<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'value_type',
        'value',
        'applies_to_type',
        'condition_type',
        'is_automatic',
        'is_stackable',
        'is_recurring',
        'valid_from',
        'valid_until',
        'max_uses',
        'used_count',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value'        => 'decimal:2',
            'is_automatic' => 'boolean',
            'is_stackable'  => 'boolean',
            'is_recurring' => 'boolean',
            'valid_from'   => 'date',
            'valid_until'  => 'date',
            'active'       => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(DiscountApplication::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true)->where('active', true);
    }

    public function isValid(): bool
    {
        if (!$this->active) return false;
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    /**
     * Calcula el monto de descuento dado un subtotal.
     */
    public function calculateAmount(float $subtotal): float
    {
        if ($this->value_type === 'percentage') {
            return round($subtotal * ($this->value / 100), 2);
        }
        return (float) $this->value;
    }
}