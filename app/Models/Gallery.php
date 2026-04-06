<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gallery extends Model
{
    protected $fillable = [
        'title',
        'description',
        'galleryable_id',
        'galleryable_type',
        'created_by',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Relación polimórfica
    public function galleryable(): MorphTo
    {
        return $this->morphTo();
    }

    // Fotos de la galería
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('order');
    }

    // Usuario que creó la galería
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Helpers
    public function getPhotosCountAttribute(): int
    {
        return $this->photos()->count();
    }

    public function getCoverPhotoAttribute()
    {
        return $this->photos()->first();
    }
}
