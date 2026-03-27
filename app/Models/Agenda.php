<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'capacity',
        'is_active',
        'available_days',
        'open_time',
        'close_time',
        'requires_qr',
        'qr_room_code',
        'color',
        'icon',
        'created_by',
    ];

    protected $casts = [
        'available_days' => 'array',
        'requires_qr'    => 'boolean',
        'is_active'      => 'boolean',
    ];

    // Relaciones
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRooms($query)
    {
        return $query->where('type', 'room');
    }

    public function scopeCalendars($query)
    {
        return $query->where('type', 'calendar');
    }

    // Helper
    public function hasPhysicalQr(): bool
    {
        return $this->requires_qr && !empty($this->qr_room_code);
    }
}
