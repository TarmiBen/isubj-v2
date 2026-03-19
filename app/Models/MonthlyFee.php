<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'monthly_fee_config_id',
        'inscription_id',
        'month',
        'year',
        'period_start',
        'period_end',
        'payment_order_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'month'        => 'integer',
            'year'         => 'integer',
            'period_start' => 'date',
            'period_end'   => 'date',
        ];
    }

    // ── Polimorfismo: esto es lo que referencia payment_orders.chargeable ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(MonthlyFeeConfig::class, 'monthly_fee_config_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}