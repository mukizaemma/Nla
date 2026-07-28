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

        $mime = (string) ($file->getMimeType() ?: '');
        try {
            $processed = self::processToMaxBytes($realPath ?: $file->getRealPath(), $mime, $originalBytes);
            $final = $processed['bytes'];
        } catch (\Throwable $e) {
            $final = self::MAX_BYTES;
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
            throw new RuntimeException('Unable to process this image. Try JPG or PNG.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = 1.0;
        $quality = 85;
        $binary = '';
        $outMime = 'image/jpeg';

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $canvas = imagecreatetruecolor($newW, $newH);
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
                $scale *= 0.85;
                $quality = 80;
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
            'width' => max(1, (int) round($width * $scale)),
            'height' => max(1, (int) round($height * $scale)),
            'was_resized' => true,
        ];
    }

    /** @return \GdImage|resource|null */
    protected static function loadGdImage(string $path, string $mime)
    {
        $mime = strtolower($mime);
        try {
            return match (true) {
                str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
                str_contains($mime, 'png') => @imagecreatefrompng($path),
                str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
                str_contains($mime, 'gif') => @imagecreatefromgif($path),
                default => @imagecreatefromstring((string) file_get_contents($path)),
            } ?: null;
        } catch (\Throwable $e) {
            return null;
        }
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
