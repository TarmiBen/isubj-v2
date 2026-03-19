<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyFeeConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_concept_id',
        'generation_id',
        'amount',
        'generation_day',
        'due_days',
        'months_count',
        'start_month',
        'start_year',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'generation_day' => 'integer',
            'due_days'       => 'integer',
            'months_count'   => 'integer',
            'start_month'    => 'integer',
            'start_year'     => 'integer',
            'active'         => 'boolean',
        ];
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(PaymentConcept::class, 'payment_concept_id');
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function monthlyFees(): HasMany
    {
        return $this->hasMany(MonthlyFee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}