<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class AdminImageUploader
{
    public const MIN_BYTES = 300 * 1024; // 300KB

    public const MAX_BYTES = 700 * 1024; // 700KB

    public const ABSOLUTE_UPLOAD_MAX_KB = 8192; // Livewire temp upload ceiling

    /**
     * Process an uploaded image, enforce size rules, store on the public disk,
     * and register it in the media library.
     *
     * @return array{path: string, bytes: int, width: int|null, height: int|null, was_resized: bool, media: MediaAsset}
     */
    public static function store(
        UploadedFile|TemporaryUploadedFile $file,
        string $folder,
        bool $allowSmall = false,
        ?string $source = null,
    ): array {
        $realPath = $file->getRealPath();
        if (! $realPath || ! is_readable($realPath)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        $originalBytes = (int) filesize($realPath);
        if ($originalBytes <= 0) {
            throw new RuntimeException('Uploaded image is empty.');
        }

        if (! $allowSmall && $originalBytes < self::MIN_BYTES) {
            throw new RuntimeException(
                'Image is too small ('.self::formatBytes($originalBytes).'). '
                .'Please upload an image of at least 300KB (logos may be smaller).'
            );
        }

        $mime = (string) ($file->getMimeType() ?: mime_content_type($realPath) ?: '');
        if (! str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Only image files are allowed.');
        }

        $processed = self::processToMaxBytes($realPath, $mime, $originalBytes);
        $extension = self::extensionForMime($processed['mime']);
        $filename = Str::uuid()->toString().'.'.$extension;
        $relative = trim($folder, '/').'/'.$filename;

        Storage::disk('public')->put($relative, $processed['binary']);

        $publicPath = 'storage/'.$relative;
        $hash = hash('sha256', $processed['binary']);

        $media = MediaAsset::query()->firstOrCreate(
            ['hash' => $hash],
            [
                'path' => $publicPath,
                'disk_path' => $relative,
                'folder' => $folder,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $processed['mime'],
                'bytes' => $processed['bytes'],
                'width' => $processed['width'],
                'height' => $processed['height'],
                'source' => $source,
            ]
        );

        // If hash existed but path differs, keep existing media record (duplicate reuse).
        if ($media->wasRecentlyCreated === false && $media->path !== $publicPath) {
            // Remove the just-written duplicate file and reuse the existing asset.
            Storage::disk('public')->delete($relative);

            return [
                'path' => $media->path,
                'bytes' => (int) $media->bytes,
                'width' => $media->width,
                'height' => $media->height,
                'was_resized' => $processed['was_resized'],
                'media' => $media,
                'reused' => true,
            ];
        }

        return [
            'path' => $publicPath,
            'bytes' => $processed['bytes'],
            'width' => $processed['width'],
            'height' => $processed['height'],
            'was_resized' => $processed['was_resized'],
            'media' => $media,
            'reused' => false,
        ];
    }

    /**
     * Inspect a temporary upload and return the size that would be stored
     * after compression rules (without writing to disk).
     *
     * @return array{original_bytes: int, final_bytes: int, will_resize: bool, allowed: bool, message: ?string}
     */
    public static function preview(UploadedFile|TemporaryUploadedFile $file, bool $allowSmall = false): array
    {
        $realPath = $file->getRealPath();
        $originalBytes = $realPath && is_readable($realPath) ? (int) filesize($realPath) : (int) $file->getSize();

        if (! $allowSmall && $originalBytes < self::MIN_BYTES) {
            return [
                'original_bytes' => $originalBytes,
                'final_bytes' => $originalBytes,
                'will_resize' => false,
                'allowed' => false,
                'message' => 'Too small (min 300KB). Logos may be smaller.',
            ];
        }

        if ($originalBytes <= self::MAX_BYTES) {
            return [
                'original_bytes' => $originalBytes,
                'final_bytes' => $originalBytes,
                'will_resize' => false,
                'allowed' => true,
                'message' => 'Ready — will upload at '.self::formatBytes($originalBytes).' (no resize needed).',
            ];
        }

        $path = $realPath ?: $file->getRealPath();
        $mime = (string) ($file->getMimeType() ?: mime_content_type($path) ?: '');
        try {
            $processed = self::processToMaxBytes((string) $path, $mime, $originalBytes);
            $final = $processed['bytes'];
        } catch (\Throwable $e) {
            return [
                'original_bytes' => $originalBytes,
                'final_bytes' => $originalBytes,
                'will_resize' => true,
                'allowed' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'original_bytes' => $originalBytes,
            'final_bytes' => $final,
            'will_resize' => true,
            'allowed' => true,
            'message' => 'Will be resized from '.self::formatBytes($originalBytes)
                .' to about '.self::formatBytes($final).' (max 700KB).',
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }

    /**
     * Register an existing public storage path into the media library.
     */
    public static function registerExisting(string $publicPath, ?string $folder = null, ?string $source = null): ?MediaAsset
    {
        $publicPath = ltrim($publicPath, '/');
        if (! str_starts_with($publicPath, 'storage/')) {
            return null;
        }

        $diskPath = substr($publicPath, strlen('storage/'));
        if (! Storage::disk('public')->exists($diskPath)) {
            return null;
        }

        $binary = Storage::disk('public')->get($diskPath);
        $hash = hash('sha256', $binary);
        $bytes = strlen($binary);
        $mime = Storage::disk('public')->mimeType($diskPath) ?: 'image/jpeg';

        $width = null;
        $height = null;
        $info = @getimagesizefromstring($binary);
        if (is_array($info)) {
            $width = $info[0] ?? null;
            $height = $info[1] ?? null;
        }

        return MediaAsset::query()->firstOrCreate(
            ['hash' => $hash],
            [
                'path' => $publicPath,
                'disk_path' => $diskPath,
                'folder' => $folder ?: trim(dirname($diskPath), '.'),
                'original_name' => basename($diskPath),
                'mime' => $mime,
                'bytes' => $bytes,
                'width' => $width,
                'height' => $height,
                'source' => $source,
            ]
        );
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}
     */
    protected static function processToMaxBytes(string $realPath, string $mime, int $originalBytes): array
    {
        self::raiseMemoryLimit();

        if ($originalBytes <= self::MAX_BYTES) {
            $binary = (string) file_get_contents($realPath);
            $info = @getimagesize($realPath);

            return [
                'binary' => $binary,
                'bytes' => strlen($binary),
                'mime' => $mime ?: ((is_array($info) ? ($info['mime'] ?? null) : null) ?: 'image/jpeg'),
                'width' => is_array($info) ? ($info[0] ?? null) : null,
                'height' => is_array($info) ? ($info[1] ?? null) : null,
                'was_resized' => false,
            ];
        }

        $image = self::loadGdImage($realPath, $mime);
        if (! $image) {
            throw new RuntimeException(
                'Unable to process this image. Use a standard JPG, PNG, WEBP, or GIF '
                .'(HEIC/AVIF from some phones may need converting first).'
            );
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Cap starting dimensions so huge phone photos compress reliably.
        $maxEdge = 1920;
        $scale = 1.0;
        if (max($width, $height) > $maxEdge) {
            $scale = $maxEdge / max($width, $height);
        }

        $quality = 82;
        $binary = '';
        $outMime = 'image/jpeg';
        $newW = $width;
        $newH = $height;

        for ($attempt = 0; $attempt < 16; $attempt++) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $canvas = imagecreatetruecolor($newW, $newH);
            if ($canvas === false) {
                imagedestroy($image);
                throw new RuntimeException('Unable to allocate image canvas. Try a smaller photo.');
            }
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);

            ob_start();
            imagejpeg($canvas, null, $quality);
            $binary = (string) ob_get_clean();
            imagedestroy($canvas);

            if (strlen($binary) <= self::MAX_BYTES) {
                imagedestroy($image);

                return [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => $outMime,
                    'width' => $newW,
                    'height' => $newH,
                    'was_resized' => true,
                ];
            }

            if ($quality > 55) {
                $quality -= 8;
            } else {
                $scale *= 0.82;
                $quality = 78;
            }
        }

        imagedestroy($image);

        if ($binary === '' || strlen($binary) > self::MAX_BYTES) {
            throw new RuntimeException('Could not compress this image under 700KB. Try a smaller photo.');
        }

        return [
            'binary' => $binary,
            'bytes' => strlen($binary),
            'mime' => $outMime,
            'width' => $newW,
            'height' => $newH,
            'was_resized' => true,
        ];
    }

    protected static function raiseMemoryLimit(): void
    {
        $current = ini_get('memory_limit');
        if ($current === false || $current === '' || $current === '-1') {
            return;
        }

        $bytes = self::memoryLimitToBytes((string) $current);
        $target = 256 * 1024 * 1024;
        if ($bytes > 0 && $bytes < $target) {
            @ini_set('memory_limit', '256M');
        }
    }

    protected static function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }

    /** @return \GdImage|resource|null */
    protected static function loadGdImage(string $path, string $mime = '')
    {
        if (! is_readable($path)) {
            return null;
        }

        $info = @getimagesize($path);
        $detectedType = is_array($info) ? (int) ($info[2] ?? 0) : 0;
        $detectedMime = is_array($info) ? strtolower((string) ($info['mime'] ?? '')) : '';
        $mime = strtolower($mime ?: $detectedMime);

        // Prefer real file-type detection over the uploaded MIME (browsers often lie).
        $loaders = [];

        if ($detectedType === IMAGETYPE_JPEG || str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
            $loaders[] = static fn () => @imagecreatefromjpeg($path);
        }
        if ($detectedType === IMAGETYPE_PNG || str_contains($mime, 'png')) {
            $loaders[] = static fn () => @imagecreatefrompng($path);
        }
        if (($detectedType === IMAGETYPE_WEBP || str_contains($mime, 'webp')) && function_exists('imagecreatefromwebp')) {
            $loaders[] = static fn () => @imagecreatefromwebp($path);
        }
        if ($detectedType === IMAGETYPE_GIF || str_contains($mime, 'gif')) {
            $loaders[] = static fn () => @imagecreatefromgif($path);
        }
        if (($detectedType === IMAGETYPE_BMP || str_contains($mime, 'bmp')) && function_exists('imagecreatefrombmp')) {
            $loaders[] = static fn () => @imagecreatefrombmp($path);
        }
        if (function_exists('imagecreatefromavif') && (str_contains($mime, 'avif') || str_contains($detectedMime, 'avif'))) {
            $loaders[] = static fn () => @imagecreatefromavif($path);
        }

        // Always finish with binary decode as a format-agnostic fallback.
        $loaders[] = static fn () => @imagecreatefromstring((string) file_get_contents($path));

        foreach ($loaders as $loader) {
            try {
                $image = $loader();
                if ($image) {
                    return $image;
                }
            } catch (\Throwable $e) {
                // try next loader
            }
        }

        return null;
    }

    protected static function extensionForMime(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            default => 'jpg',
        };
    }
}
