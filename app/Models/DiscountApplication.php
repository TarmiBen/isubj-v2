<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_order_id',
        'discount_id',
        'discount_amount',
        'discount_percentage',
        'applied_by_type',
        'applied_by',
        'applied_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount'      => 'decimal:2',
            'discount_percentage'  => 'decimal:2',
            'applied_at'           => 'datetime',
        ];
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}