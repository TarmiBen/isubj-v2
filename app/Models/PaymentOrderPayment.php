<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentOrderPayment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('payment_order_payment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'payment_order_payments';

    protected $fillable = [
        'payment_id',
        'payment_order_id',
        'amount_applied',
        'days_late',
    ];

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:2',
            'days_late'      => 'integer',
        ];
    }

    public function isLate(): bool
    {
        return (int) $this->days_late > 0;
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
