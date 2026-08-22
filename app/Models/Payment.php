<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('payment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
        $year   = now()->year;
        $prefix = "PAY-{$year}-";

        // Basado en el último folio existente (no en un conteo de filas), para
        // no chocar cuando hay huecos por soft-deletes o transacciones revertidas.
        $lastFolio = static::withTrashed()
            ->where('folio', 'like', "{$prefix}%")
            ->orderByDesc('folio')
            ->lockForUpdate()
            ->value('folio');

        $next = $lastFolio ? ((int) substr($lastFolio, strlen($prefix))) + 1 : 1;

        return sprintf('%s%06d', $prefix, $next);
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

    // ── Tipo de cobertura (abono / pago completo / anticipo) ──────────────────

    public function getCoverageTypeAttribute(): string
    {
        $orders = $this->relationLoaded('orders') ? $this->orders : $this->orders()->get();

        if ($orders->isEmpty()) {
            return 'anticipo';
        }

        return $orders->every(fn (PaymentOrder $order) => $order->status === 'paid')
            ? 'completo'
            : 'parcial';
    }

    public function getCoverageLabelAttribute(): string
    {
        return match ($this->coverage_type) {
            'completo' => 'Pago completo',
            'parcial'  => 'Abono / Parcial',
            'anticipo' => 'Anticipo sin aplicar',
        };
    }

    public function getCoverageColorAttribute(): string
    {
        return match ($this->coverage_type) {
            'completo' => 'success',
            'parcial'  => 'warning',
            'anticipo' => 'gray',
        };
    }
}
