<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrderPayment extends Model
{
    use HasFactory;

    protected $table = 'payment_order_payments';

    protected $fillable = [
        'payment_id',
        'payment_order_id',
        'amount_applied',
    ];

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }
}