<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Document;
use App\Models\Generation;
use App\Models\Inscription;
use App\Models\PaymentOrder;
use App\Models\Payment;
use App\Models\Agreement;
use App\Models\Referral;
use App\Models\MonthlyFee;
//use Spatie\Activitylog\Traits\LogsActivity;
//use Spatie\Activitylog\LogOptions;

class Student extends Model
{
    use HasFactory; //LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_number',
        'name',
        'last_name1',
        'last_name2',
        'gender',
        'date_of_birth',
        'curp',
        'email',
        'phone',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'guardian_name',
        'guardian_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'photo',
        'photo_thumb',
        'code',
        'generation_id',
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
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
        ];
    }

    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function lastInscription()
    {
        return $this->hasOne(Inscription::class)->latestOfMany();
    }

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }


    // public function getActivitylogOptions() : LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logAll()
    //         ->useLogName('student')
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->last_name1} {$this->last_name2}";
    }

    public function getLastInscriptionAttribute()
    {
        return $this->inscriptions()->with('group')->latest()->first();
    }

    // ── Pagos ────────────────────────────────────────────────────────────────

    public function paymentOrders()
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function monthlyFees()
    {
        return $this->hasMany(MonthlyFee::class);
    }

    /** Referidos que este alumno generó (él es el referidor) */
    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_student_id');
    }

    /** Referido: quién lo refirió a él */
    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'referred_student_id');
    }

    /** Saldo pendiente total */
    public function getPendingBalanceAttribute(): float
    {
        return (float) $this->paymentOrders()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance');
    }

    /** Descuentos activos por referidos que aplican a mensualidades */
    public function activeReferralDiscounts()
    {
        return $this->referralsMade()
            ->where('status', 'active')
            ->with('discount')
            ->get()
            ->filter(fn ($r) => $r->isEligible())
            ->map(fn ($r) => $r->discount);
    }
}
