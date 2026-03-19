<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folio',
        'student_id',
        'type',
        'original_due_date',
        'new_due_date',
        'extra_days',
        'installments_count',
        'installment_amount',
        'first_installment_date',
        'total_amount',
        'paid_amount',
        'status',
        'terms',
        'notes',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'original_due_date'      => 'date',
            'new_due_date'           => 'date',
            'first_installment_date' => 'date',
            'total_amount'           => 'decimal:2',
            'paid_amount'            => 'decimal:2',
            'installment_amount'     => 'decimal:2',
            'approved_at'            => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agreement $agreement) {
            if (empty($agreement->folio)) {
                $agreement->folio = static::generateFolio();
            }
        });
    }

    public static function generateFolio(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('CONV-%d-%06d', $year, $count);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(AgreementInstallment::class);
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function getRemainingAttribute(): float
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }
}