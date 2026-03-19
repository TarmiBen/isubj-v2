<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PhotoService
{
    /**
     * Genera una miniatura 200x200 JPEG a partir de una foto ya subida al disco público.
     * Hace square-crop centrado antes de redimensionar.
     * Retorna el path relativo al disco público de la miniatura generada.
     */
    public static function generateThumbnail(string $photoPath, int $thumbSize = 200): string
    {
        $fullPath = Storage::disk('public')->path($photoPath);

        if (!file_exists($fullPath)) {
            return $photoPath;
        }

        $info = @getimagesize($fullPath);
        if (!$info) {
            return $photoPath;
        }

        $src = self::loadImage($fullPath, $info['mime']);
        if (!$src) {
            return $photoPath;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Square crop centrado
        $cropSize = min($w, $h);
        $cropX    = (int)(($w - $cropSize) / 2);
        $cropY    = (int)(($h - $cropSize) / 2);

        // Crear thumbnail
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagecopyresampled($thumb, $src, 0, 0, $cropX, $cropY, $thumbSize, $thumbSize, $cropSize, $cropSize);

        // Guardar junto a la original
        $thumbPath    = dirname($photoPath) . '/photo_thumb.jpg';
        $fullThumbPath = Storage::disk('public')->path($thumbPath);

        self::ensureDir(dirname($fullThumbPath));
        imagejpeg($thumb, $fullThumbPath, 78);

        imagedestroy($src);
        imagedestroy($thumb);

        return $thumbPath;
    }

    /**
     * Redimensiona y square-cropea la foto original in-place a máximo $maxSide px.
     * Útil para reducir el peso de imágenes grandes subidas desde el formulario.
     */
    public static function optimizeOriginal(string $photoPath, int $maxSide = 1200): void
    {
        $fullPath = Storage::disk('public')->path($photoPath);

        if (!file_exists($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);
        if (!$info) {
            return;
        }

        $w = $info[0];
        $h = $info[1];

        // Si ya es cuadrada y pequeña, solo recomprimir
        $cropSize = min($w, $h);
        $cropX    = (int)(($w - $cropSize) / 2);
        $cropY    = (int)(($h - $cropSize) / 2);
        $destSize = min($cropSize, $maxSide);

        $src = self::loadImage($fullPath, $info['mime']);
        if (!$src) {
            return;
        }

        $dest = imagecreatetruecolor($destSize, $destSize);
        imagecopyresampled($dest, $src, 0, 0, $cropX, $cropY, $destSize, $destSize, $cropSize, $cropSize);

        imagejpeg($dest, $fullPath, 85);

        imagedestroy($src);
        imagedestroy($dest);
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private static function loadImage(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif'  => imagecreatefromgif($path),
            default      => null,
        };
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
