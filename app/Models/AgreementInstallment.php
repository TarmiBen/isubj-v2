<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'   => 'date',
            'paid_at'    => 'date',
            'amount'     => 'decimal:2',
            'paid_amount'=> 'decimal:2',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getRemainingAttribute(): float
    {
        return (float) ($this->amount - $this->paid_amount);
    }
}