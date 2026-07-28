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
     * @return array{path: string, bytes: int, width: int|null, height: int|null, was_resized: bool, media: MediaAsset, reused: bool}
     */
    public static function store(
        UploadedFile|TemporaryUploadedFile $file,
        string $folder,
        bool $allowSmall = false,
        ?string $source = null,
    ): array {
        $local = self::materializeLocalFile($file);
        $cleanup = $local['cleanup'];

        try {
            $realPath = $local['path'];
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

            $processed = self::processToMaxBytes($realPath, $originalBytes);
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
        } finally {
            if ($cleanup && is_file($cleanup)) {
                @unlink($cleanup);
            }
        }
    }

    /**
     * Inspect a temporary upload and return the size that would be stored
     * after compression rules (without writing to disk).
     *
     * @return array{original_bytes: int, final_bytes: int, will_resize: bool, allowed: bool, message: ?string}
     */
    public static function preview(UploadedFile|TemporaryUploadedFile $file, bool $allowSmall = false): array
    {
        $local = self::materializeLocalFile($file);
        $cleanup = $local['cleanup'];

        try {
            $realPath = $local['path'];
            $originalBytes = is_readable($realPath) ? (int) filesize($realPath) : (int) $file->getSize();

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

            try {
                $processed = self::processToMaxBytes($realPath, $originalBytes);
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
        } finally {
            if ($cleanup && is_file($cleanup)) {
                @unlink($cleanup);
            }
        }
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
     * Copy Livewire/temp uploads into a real local file GD can read.
     *
     * @return array{path: string, cleanup: ?string}
     */
    protected static function materializeLocalFile(UploadedFile|TemporaryUploadedFile $file): array
    {
        if ($file instanceof TemporaryUploadedFile) {
            $contents = $file->get();
            if ($contents === false || $contents === null || $contents === '') {
                throw new RuntimeException('Uploaded image could not be read.');
            }

            $tmp = tempnam(sys_get_temp_dir(), 'nlaimg_');
            if ($tmp === false) {
                throw new RuntimeException('Could not create a temporary file for image processing.');
            }

            // Keep an extension hint for tools like sips / finfo.
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif', 'avif'], true)) {
                $ext = 'img';
            }
            $path = $tmp.'.'.$ext;
            @unlink($tmp);
            if (file_put_contents($path, $contents) === false) {
                throw new RuntimeException('Could not write temporary image for processing.');
            }

            return ['path' => $path, 'cleanup' => $path];
        }

        $realPath = $file->getRealPath();
        if (! $realPath || ! is_readable($realPath)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        return ['path' => $realPath, 'cleanup' => null];
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}
     */
    protected static function processToMaxBytes(string $realPath, int $originalBytes): array
    {
        self::raiseMemoryLimit();

        // Prefer macOS sips for large / stubborn images (handles CMYK, huge phone JPEGs, HEIC).
        if ($originalBytes > self::MAX_BYTES || ! self::canGdLoad($realPath)) {
            $sipsResult = self::compressWithSips($realPath);
            if ($sipsResult !== null) {
                return $sipsResult;
            }
        }

        $workingPath = self::ensureGdReadable($realPath);
        $cleanupWorking = $workingPath !== $realPath ? $workingPath : null;

        try {
            if ($originalBytes <= self::MAX_BYTES && $workingPath === $realPath) {
                $binary = (string) file_get_contents($workingPath);
                $info = @getimagesize($workingPath);

                return [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => (is_array($info) ? ($info['mime'] ?? null) : null) ?: 'image/jpeg',
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'was_resized' => false,
                ];
            }

            $workingBytes = (int) filesize($workingPath);
            if ($workingBytes <= self::MAX_BYTES) {
                $binary = (string) file_get_contents($workingPath);
                $info = @getimagesize($workingPath);

                return [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => (is_array($info) ? ($info['mime'] ?? null) : null) ?: 'image/jpeg',
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'was_resized' => $workingPath !== $realPath,
                ];
            }

            $image = self::loadGdImage($workingPath);
            if (! $image) {
                // Last resort: sips again after GD prep failed.
                $sipsResult = self::compressWithSips($workingPath) ?? self::compressWithSips($realPath);
                if ($sipsResult !== null) {
                    return $sipsResult;
                }

                $format = self::detectFormat($workingPath) ?: self::detectFormat($realPath);
                \Log::warning('Admin image processing failed', [
                    'format' => $format,
                    'bytes' => $originalBytes,
                    'path' => basename($realPath),
                    'sips' => self::sipsBinary(),
                ]);
                throw new RuntimeException(
                    'Could not resize this JPEG/PNG ('.self::formatBytes($originalBytes).'). '
                    .'Try a smaller image, or re-export it from Photos/Preview as JPEG.'
                );
            }

            $width = imagesx($image);
            $height = imagesy($image);

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
        } finally {
            if ($cleanupWorking && is_file($cleanupWorking)) {
                @unlink($cleanupWorking);
            }
        }
    }

    /**
     * Resize + compress with macOS sips (no GD decode required).
     *
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}|null
     */
    protected static function compressWithSips(string $path): ?array
    {
        $sips = self::sipsBinary();
        if ($sips === null || ! is_readable($path)) {
            return null;
        }

        $edges = [1920, 1600, 1280, 1024, 800];
        $qualities = [70, 60, 50, 40, 30];

        foreach ($edges as $edge) {
            foreach ($qualities as $quality) {
                $out = tempnam(sys_get_temp_dir(), 'nlacomp_');
                if ($out === false) {
                    return null;
                }
                $target = $out.'.jpg';
                @unlink($out);

                $cmd = escapeshellarg($sips)
                    .' -s format jpeg'
                    .' -s formatOptions '.escapeshellarg((string) $quality)
                    .' --resampleHeightWidthMax '.escapeshellarg((string) $edge).' '
                    .escapeshellarg($path)
                    .' --out '
                    .escapeshellarg($target)
                    .' 2>&1';

                $output = [];
                $code = 0;
                exec($cmd, $output, $code);

                if ($code !== 0 || ! is_file($target) || filesize($target) < 32) {
                    @unlink($target);
                    continue;
                }

                // Accept by magic bytes — do not require GD (avoids memory false-negatives).
                if (self::detectFormat($target) !== 'jpeg') {
                    @unlink($target);
                    continue;
                }

                $bytes = (int) filesize($target);
                if ($bytes > self::MAX_BYTES) {
                    @unlink($target);
                    continue;
                }

                $binary = (string) file_get_contents($target);
                $info = @getimagesize($target);
                @unlink($target);

                return [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => 'image/jpeg',
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'was_resized' => true,
                ];
            }
        }

        return null;
    }

    /**
     * Make sure the file is in a format GD can decode (convert HEIC/etc. on macOS via sips).
     * Also downscales very large images via sips first so PHP/GD does not run out of memory.
     */
    protected static function ensureGdReadable(string $path): string
    {
        $working = $path;

        if (! self::canGdLoad($working)) {
            $format = self::detectFormat($working);
            $converted = self::runSips($working, [
                '-s', 'format', 'jpeg',
                '-s', 'formatOptions', '80',
                '--resampleHeightWidthMax', '1920',
            ]);
            if ($converted) {
                $working = $converted;
            } elseif ($format === 'heic' || $format === 'heif') {
                throw new RuntimeException(
                    'This looks like an iPhone HEIC photo saved with a .JPEG name. '
                    .'Export it as JPG in Photos (File → Export → JPEG), or try another image.'
                );
            }
        }

        $info = @getimagesize($working);
        if (is_array($info) && (int) filesize($working) > self::MAX_BYTES) {
            $w = (int) ($info[0] ?? 0);
            $h = (int) ($info[1] ?? 0);
            if (max($w, $h) > 2200) {
                $resized = self::runSips($working, [
                    '-s', 'format', 'jpeg',
                    '-s', 'formatOptions', '80',
                    '--resampleHeightWidthMax', '1920',
                ]);
                if ($resized) {
                    if ($working !== $path && is_file($working)) {
                        @unlink($working);
                    }
                    $working = $resized;
                }
            }
        }

        return $working;
    }

    /**
     * @param  list<string>  $args
     */
    protected static function runSips(string $path, array $args): ?string
    {
        $sips = self::sipsBinary();
        if ($sips === null) {
            return null;
        }

        $out = tempnam(sys_get_temp_dir(), 'nlasips_');
        if ($out === false) {
            return null;
        }
        $target = $out.'.jpg';
        @unlink($out);

        $cmd = escapeshellarg($sips);
        foreach ($args as $arg) {
            $cmd .= ' '.escapeshellarg($arg);
        }
        $cmd .= ' '.escapeshellarg($path).' --out '.escapeshellarg($target).' 2>&1';

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code !== 0 || ! is_file($target) || filesize($target) < 32) {
            @unlink($target);

            return null;
        }

        if (self::detectFormat($target) !== 'jpeg') {
            @unlink($target);

            return null;
        }

        return $target;
    }

    protected static function sipsBinary(): ?string
    {
        static $cached;
        if ($cached !== null) {
            return $cached ?: null;
        }

        foreach (['/usr/bin/sips', trim((string) shell_exec('command -v sips 2>/dev/null'))] as $candidate) {
            if ($candidate !== '' && is_executable($candidate)) {
                $cached = $candidate;

                return $cached;
            }
        }

        $cached = '';

        return null;
    }

    protected static function canGdLoad(string $path): bool
    {
        $image = self::loadGdImage($path);
        if (! $image) {
            return false;
        }
        imagedestroy($image);

        return true;
    }

    protected static function detectFormat(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return null;
        }
        $bytes = (string) fread($handle, 64);
        fclose($handle);

        if ($bytes === '') {
            return null;
        }

        $hex = bin2hex(substr($bytes, 0, 12));

        if (str_starts_with($hex, 'ffd8ff')) {
            return 'jpeg';
        }
        if (str_starts_with($hex, '89504e470d0a1a0a')) {
            return 'png';
        }
        if (str_starts_with($bytes, 'GIF8')) {
            return 'gif';
        }
        if (str_starts_with($bytes, 'BM')) {
            return 'bmp';
        }
        if (str_starts_with($bytes, 'RIFF') && str_contains(substr($bytes, 0, 16), 'WEBP')) {
            return 'webp';
        }
        if (str_starts_with($bytes, 'II*\x00') || str_starts_with($bytes, 'MM\x00*')) {
            return 'tiff';
        }

        // ISO BMFF brands (HEIC/HEIF/AVIF): bytes 4..8 = "ftyp"
        if (strlen($bytes) >= 12 && substr($bytes, 4, 4) === 'ftyp') {
            $brand = strtolower(substr($bytes, 8, 4));
            if (in_array($brand, ['heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1', 'heim', 'heis'], true)) {
                return 'heic';
            }
            if (in_array($brand, ['heif'], true)) {
                return 'heif';
            }
            if (in_array($brand, ['avif', 'avis'], true)) {
                return 'avif';
            }

            // Some HEIF files use other brands; sniff remaining header.
            $header = strtolower($bytes);
            if (str_contains($header, 'heic') || str_contains($header, 'heif')) {
                return 'heic';
            }
            if (str_contains($header, 'avif')) {
                return 'avif';
            }

            return 'unknown';
        }

        return 'unknown';
    }

    protected static function raiseMemoryLimit(): void
    {
        $current = ini_get('memory_limit');
        if ($current === false || $current === '' || $current === '-1') {
            return;
        }

        $bytes = self::memoryLimitToBytes((string) $current);
        $target = 512 * 1024 * 1024;
        if ($bytes > 0 && $bytes < $target) {
            @ini_set('memory_limit', '512M');
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
    protected static function loadGdImage(string $path)
    {
        if (! is_readable($path)) {
            return null;
        }

        $info = @getimagesize($path);
        $detectedType = is_array($info) ? (int) ($info[2] ?? 0) : 0;

        $loaders = [];

        // Prefer binary decode first — ignores wrong extensions/MIME.
        $loaders[] = static function () use ($path) {
            $data = @file_get_contents($path);

            return $data !== false ? @imagecreatefromstring($data) : false;
        };

        if ($detectedType === IMAGETYPE_JPEG || $detectedType === 0) {
            $loaders[] = static fn () => @imagecreatefromjpeg($path);
        }
        if ($detectedType === IMAGETYPE_PNG || $detectedType === 0) {
            $loaders[] = static fn () => @imagecreatefrompng($path);
        }
        if (($detectedType === IMAGETYPE_WEBP || $detectedType === 0) && function_exists('imagecreatefromwebp')) {
            $loaders[] = static fn () => @imagecreatefromwebp($path);
        }
        if ($detectedType === IMAGETYPE_GIF || $detectedType === 0) {
            $loaders[] = static fn () => @imagecreatefromgif($path);
        }
        if (($detectedType === IMAGETYPE_BMP || $detectedType === 0) && function_exists('imagecreatefrombmp')) {
            $loaders[] = static fn () => @imagecreatefrombmp($path);
        }
        if (function_exists('imagecreatefromavif')) {
            $loaders[] = static fn () => @imagecreatefromavif($path);
        }

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
