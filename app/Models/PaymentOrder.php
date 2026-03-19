<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folio',
        'student_id',
        'payment_concept_id',
        'chargeable_type',
        'chargeable_id',
        'period_start',
        'period_end',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'balance',
        'due_date',
        'paid_at',
        'status',
        'agreement_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'    => 'date',
            'period_end'      => 'date',
            'due_date'        => 'date',
            'paid_at'         => 'date',
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'balance'         => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentOrder $order) {
            if (empty($order->folio)) {
                $order->folio = static::generateFolio();
            }
        });
    }

    public static function generateFolio(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('PO-%d-%06d', $year, $count);
    }

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(PaymentConcept::class, 'payment_concept_id');
    }

    public function chargeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function discountApplications(): HasMany
    {
        return $this->hasMany(DiscountApplication::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_order_payments')
                    ->withPivot('amount_applied')
                    ->withTimestamps();
    }

    public function orderPayments(): HasMany
    {
        return $this->hasMany(PaymentOrderPayment::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(fn ($q) => $q->whereIn('status', ['pending', 'partial'])->where('due_date', '<', now()));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Aplica un abono y actualiza balance / status.
     */
    public function applyPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance      = max(0, $this->total - $this->paid_amount);

        if ($this->balance <= 0) {
            $this->status  = 'paid';
            $this->paid_at = now()->toDateString();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }

        $this->save();
    }

    public function isFullyPaid(): bool
    {
        return $this->balance <= 0;
    }
}