<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    protected $fillable = [
        'gallery_id',
        'filename',
        'path',
        'thumbnail_path',
        'original_filename',
        'size',
        'original_size',
        'mime_type',
        'width',
        'height',
        'caption',
        'order',
    ];

    protected $casts = [
        'size' => 'integer',
        'original_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
    ];

    // Relación con galería
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    // Helpers
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : $this->url;
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->size / 1024 / 1024, 2);
    }

    public function getOriginalSizeInMbAttribute(): ?float
    {
        return $this->original_size ? round($this->original_size / 1024 / 1024, 2) : null;
    }

    // Eliminar archivos al borrar registro
    protected static function booted()
    {
        static::deleting(function ($photo) {
            if ($photo->path && Storage::exists($photo->path)) {
                Storage::delete($photo->path);
            }
            if ($photo->thumbnail_path && Storage::exists($photo->thumbnail_path)) {
                Storage::delete($photo->thumbnail_path);
            }
        });
    }
}
