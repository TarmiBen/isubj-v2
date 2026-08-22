<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('payment_order')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
        'parent_payment_order_id',
        'is_surcharge',
        'surcharge_rate',
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
            'is_surcharge'    => 'boolean',
            'surcharge_rate'  => 'decimal:2',
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
        $year   = now()->year;
        $prefix = "PO-{$year}-";

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

    /** Adeudo original del que se derivó este recargo. */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'parent_payment_order_id');
    }

    /** Recargos por mora generados a partir de este adeudo. */
    public function surcharges(): HasMany
    {
        return $this->hasMany(PaymentOrder::class, 'parent_payment_order_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    public function scopeOverdue($query)
    {
        return $query->where(fn ($q) => $q->where('status', 'overdue')
            ->orWhere(fn ($q2) => $q2->whereIn('status', ['pending', 'partial'])->where('due_date', '<', now())));
    }

    public function scopeSurcharges($query)
    {
        return $query->where('is_surcharge', true);
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

    // ── Vencimiento efectivo (considera convenios) ───────────────────────────

    /**
     * Convenio que corre el vencimiento de este adeudo, si lo hay.
     *
     * Primero el convenio ligado explícitamente (`agreement_id`); si no,
     * el convenio vigente del alumno. No se hace coincidir por fechas: el
     * dato que manda es `extra_days`.
     */
    public function appliedAgreement(): ?Agreement
    {
        if ($this->agreement_id) {
            $explicit = $this->relationLoaded('agreement')
                ? $this->agreement
                : $this->agreement()->first();

            if ($explicit?->grantsExtension()) {
                return $explicit;
            }
        }

        $student = $this->relationLoaded('student') ? $this->student : null;

        if ($student === null) {
            $student = $this->student()->with('activeAgreements')->first();
        }

        if ($student === null) {
            return null;
        }

        $agreements = $student->relationLoaded('activeAgreements')
            ? $student->activeAgreements
            : $student->activeAgreements()->get();

        // El más reciente gana: un convenio nuevo sustituye al anterior.
        return $agreements
            ->filter(fn (Agreement $a) => $a->grantsExtension())
            ->sortByDesc('created_at')
            ->first();
    }

    /** Días extra concedidos por convenio. 0 si no hay. */
    public function agreementExtraDays(): int
    {
        return $this->appliedAgreement()?->extra_days ?? 0;
    }

    /**
     * Fecha límite real: la del adeudo más los días extra del convenio.
     */
    public function effectiveDueDate(): ?\Carbon\Carbon
    {
        if (! $this->due_date) {
            return null;
        }

        $due = $this->due_date->copy()->startOfDay();
        $extra = $this->agreementExtraDays();

        return $extra > 0 ? $due->addDays($extra) : $due;
    }

    /**
     * Días de atraso de una fecha dada respecto al vencimiento efectivo.
     * 0 = a tiempo.
     */
    public function daysLateFor(mixed $date): int
    {
        $due = $this->effectiveDueDate();

        if (! $due) {
            return 0;
        }

        $date = $date instanceof \DateTimeInterface ? \Carbon\Carbon::instance($date) : \Carbon\Carbon::parse($date);

        return max(0, $due->diffInDays($date->startOfDay(), false));
    }

    public function isOverdue(): bool
    {
        return $this->balance > 0
            && ! in_array($this->status, ['paid', 'cancelled'], true)
            && $this->daysLateFor(now()) > 0;
    }

    /** Porcentaje pagado del adeudo (0-100). */
    public function getProgressPercentAttribute(): float
    {
        if ($this->total <= 0) {
            return 100.0;
        }

        return min(100, round(($this->paid_amount / $this->total) * 100, 1));
    }
}