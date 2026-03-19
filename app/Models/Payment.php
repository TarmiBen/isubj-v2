<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folio',
        'student_id',
        'payment_method_id',
        'amount_received',
        'amount_applied',
        'change_amount',
        'payment_date',
        'receipt_number',
        'status',
        'notes',
        'received_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_received' => 'decimal:2',
            'amount_applied'  => 'decimal:2',
            'change_amount'   => 'decimal:2',
            'payment_date'    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->folio)) {
                $payment->folio = static::generateFolio();
            }
        });
    }

    public static function generateFolio(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('PAY-%d-%06d', $year, $count);
    }

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): HasOne
    {
        return $this->hasOne(PaymentReference::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(PaymentOrder::class, 'payment_order_payments')
                    ->withPivot('amount_applied')
                    ->withTimestamps();
    }

    public function orderPayments(): HasMany
    {
        return $this->hasMany(PaymentOrderPayment::class);
    }
}
