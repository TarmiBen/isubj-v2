<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_student_id',
        'referred_student_id',
        'referral_code',
        'discount_id',
        'status',
        'requires_referred_enrolled',
        'activated_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_referred_enrolled' => 'boolean',
            'activated_at'               => 'datetime',
            'expires_at'                 => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referrer_student_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referred_student_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica si el referido sigue inscrito (activo).
     */
    public function referredIsEnrolled(): bool
    {
        return $this->referred->inscriptions()
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Verifica si el referral da descuento hoy.
     */
    public function isEligible(): bool
    {
        if (!$this->isActive()) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        if ($this->requires_referred_enrolled && !$this->referredIsEnrolled()) return false;
        return true;
    }
}