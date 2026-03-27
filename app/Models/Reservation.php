<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'purpose',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'date' => 'date',
    ];

    // Relaciones
    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    // Helpers
    public function isCheckedIn(): bool
    {
        return !empty($this->meta['check_in']) && empty($this->meta['check_out']);
    }

    public function isCompleted(): bool
    {
        return !empty($this->meta['check_out']);
    }

    public function hasSanction(): bool
    {
        return !empty($this->meta['sanctions']);
    }

    public function requiresQr(): bool
    {
        return $this->agenda->hasPhysicalQr();
    }
}
