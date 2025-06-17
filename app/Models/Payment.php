<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'payment_reference',
        'amount',
        'paid_at',
        'payments_income_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'payment_id' => 'integer',
            'user_id' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payments_income_id' => 'integer',
        ];
    }

    public function paymentsIncome(): BelongsTo
    {
        return $this->belongsTo(PaymentsIncome::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentsIncome::class);
    }
}
