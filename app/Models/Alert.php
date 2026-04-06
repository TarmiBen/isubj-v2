<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alert extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'expires_at',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relación con el creador
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con usuarios asignados (directa)
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alert_user')
            ->withPivot(['viewed_at', 'closed_at'])
            ->withTimestamps();
    }

    // Relación con grupos
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'alert_group')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('users', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeNotClosedBy($query, $userId)
    {
        return $query->whereHas('users', function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->whereNull('alert_user.closed_at');
        });
    }

    // Helpers
    public function markAsViewed(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['viewed_at' => now()]
        ]);
    }

    public function markAsClosed(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => [
                'viewed_at' => now(),
                'closed_at' => now()
            ]
        ]);
    }

    public function isViewedBy(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivotNotNull('viewed_at')
            ->exists();
    }

    public function isClosedBy(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivotNotNull('closed_at')
            ->exists();
    }

    public function getViewedCountAttribute(): int
    {
        return $this->users()->wherePivotNotNull('viewed_at')->count();
    }

    public function getClosedCountAttribute(): int
    {
        return $this->users()->wherePivotNotNull('closed_at')->count();
    }

    public function getTotalRecipientsAttribute(): int
    {
        return $this->users()->count();
    }

    public function getViewedPercentageAttribute(): float
    {
        $total = $this->total_recipients;
        return $total > 0 ? round(($this->viewed_count / $total) * 100, 1) : 0;
    }

    public function getClosedPercentageAttribute(): float
    {
        $total = $this->total_recipients;
        return $total > 0 ? round(($this->closed_count / $total) * 100, 1) : 0;
    }
}
