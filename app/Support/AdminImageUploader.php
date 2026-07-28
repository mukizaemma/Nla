<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class AdminImageUploader
{
    /** Minimum source size (logos may be smaller via $allowSmall). */
    public const MIN_BYTES = 400 * 1024; // 400KB

    /** Maximum stored size after automatic resize. */
    public const MAX_BYTES = 700 * 1024; // 700KB

    public const ABSOLUTE_UPLOAD_MAX_KB = 20480; // Livewire temp upload ceiling (20MB)

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
                // Allow under-min only when the file is already within the max cap
                // (browser may compress a huge photo slightly under 400KB).
                if ($originalBytes > self::MAX_BYTES || $originalBytes < 32 * 1024) {
                    throw new RuntimeException(
                        'Image is too small ('.self::formatBytes($originalBytes).'). '
                        .'Please upload an image of at least 400KB (logos may be smaller).'
                    );
                }
            }

            $processed = self::processToTargetRange($realPath, $originalBytes);
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
     * @return array{original_bytes: int, final_bytes: int, will_resize: bool, allowed: bool, message: ?string}
     */
    public static function preview(UploadedFile|TemporaryUploadedFile $file, bool $allowSmall = false): array
    {
        $local = self::materializeLocalFile($file);
        $cleanup = $local['cleanup'];

        try {
            $realPath = $local['path'];
            $originalBytes = is_readable($realPath) ? (int) filesize($realPath) : (int) $file->getSize();

            if (! $allowSmall && $originalBytes < 32 * 1024) {
                return [
                    'original_bytes' => $originalBytes,
                    'final_bytes' => $originalBytes,
                    'will_resize' => false,
                    'allowed' => false,
                    'message' => 'Too small (min 400KB). Logos may be smaller.',
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
                $processed = self::processToTargetRange($realPath, $originalBytes);

                return [
                    'original_bytes' => $originalBytes,
                    'final_bytes' => $processed['bytes'],
                    'will_resize' => true,
                    'allowed' => true,
                    'message' => 'Will be resized from '.self::formatBytes($originalBytes)
                        .' to '.self::formatBytes($processed['bytes']).' (target 400–700KB).',
                ];
            } catch (\Throwable $e) {
                return [
                    'original_bytes' => $originalBytes,
                    'final_bytes' => $originalBytes,
                    'will_resize' => true,
                    'allowed' => false,
                    'message' => $e->getMessage(),
                ];
            }
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
     * @return array{path: string, cleanup: ?string}
     */
    protected static function materializeLocalFile(UploadedFile|TemporaryUploadedFile $file): array
    {
        $dir = self::tempDir();

        if ($file instanceof TemporaryUploadedFile) {
            $contents = $file->get();
            if ($contents === false || $contents === null || $contents === '') {
                // Fallback: copy from Livewire storage path.
                $real = $file->getRealPath();
                if ($real && is_readable($real)) {
                    $contents = file_get_contents($real);
                }
            }
            if ($contents === false || $contents === null || $contents === '') {
                throw new RuntimeException('Uploaded image could not be read.');
            }

            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif', 'avif'], true)) {
                $ext = 'jpg';
            }
            $path = $dir.'/'.Str::uuid().'.'.$ext;
            if (file_put_contents($path, $contents) === false) {
                throw new RuntimeException('Could not write temporary image for processing.');
            }

            return ['path' => $path, 'cleanup' => $path];
        }

        $realPath = $file->getRealPath();
        if (! $realPath || ! is_readable($realPath)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        // Copy into our temp dir so sips/GD always see a stable local path.
        $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
        $path = $dir.'/'.Str::uuid().'.'.$ext;
        if (! @copy($realPath, $path)) {
            return ['path' => $realPath, 'cleanup' => null];
        }

        return ['path' => $path, 'cleanup' => $path];
    }

    protected static function tempDir(): string
    {
        $dir = storage_path('app/image-tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Resize/compress into the 400KB–700KB target band when needed.
     *
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}
     */
    protected static function processToTargetRange(string $realPath, int $originalBytes): array
    {
        self::raiseMemoryLimit();

        if ($originalBytes <= self::MAX_BYTES) {
            $binary = (string) file_get_contents($realPath);
            $info = @getimagesizefromstring($binary) ?: @getimagesize($realPath);

            return [
                'binary' => $binary,
                'bytes' => strlen($binary),
                'mime' => (is_array($info) ? ($info['mime'] ?? null) : null) ?: 'image/jpeg',
                'width' => is_array($info) ? ($info[0] ?? null) : null,
                'height' => is_array($info) ? ($info[1] ?? null) : null,
                'was_resized' => false,
            ];
        }

        // Order matters for production Linux (DigitalOcean): GD → ImageMagick → macOS sips.
        $attempts = [
            'gd' => fn () => self::compressWithGd($realPath),
            'imagick' => fn () => self::compressWithImagick($realPath),
            'imagemagick_cli' => fn () => self::compressWithImageMagickCli($realPath),
            'sips' => fn () => self::compressWithSips($realPath),
        ];

        foreach ($attempts as $engine => $attempt) {
            try {
                $result = $attempt();
                if ($result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning('Image compress engine failed', [
                    'engine' => $engine,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Last resort: convert to JPEG via CLI, then GD again.
        $converted = self::forceJpegViaCli($realPath);
        if ($converted) {
            try {
                $gd = self::compressWithGd($converted);
                if ($gd !== null) {
                    return $gd;
                }
                $imagick = self::compressWithImagick($converted);
                if ($imagick !== null) {
                    return $imagick;
                }
            } finally {
                @unlink($converted);
            }
        }

        Log::warning('Admin image resize failed', [
            'bytes' => $originalBytes,
            'format' => self::detectFormat($realPath),
            'gd' => extension_loaded('gd'),
            'imagick' => extension_loaded('imagick'),
            'magick' => self::imageMagickBinary(),
            'sips' => self::sipsBinary(),
            'exec' => function_exists('exec'),
            'memory_limit' => ini_get('memory_limit'),
        ]);

        throw new RuntimeException(
            'Could not resize this image ('.self::formatBytes($originalBytes).') into 400–700KB. '
            .'On the server, ensure PHP GD is installed (php-gd) and nginx allows uploads '
            .'(client_max_body_size 20M). Or re-export as JPG from Preview and try again.'
        );
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}|null
     */
    protected static function compressWithImagick(string $path): ?array
    {
        if (! extension_loaded('imagick') || ! class_exists(\Imagick::class)) {
            return null;
        }

        try {
            $image = new \Imagick($path);
            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            } elseif (method_exists($image, 'autoOrientImage')) {
                @$image->autoOrientImage();
            }

            $image->setImageBackgroundColor('white');
            try {
                if (method_exists($image, 'getImageAlphaChannel') && $image->getImageAlphaChannel()) {
                    if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                        $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                    }
                    if (defined('Imagick::LAYERMETHOD_FLATTEN')) {
                        $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    }
                }
            } catch (\Throwable $e) {
                // Some builds lack alpha APIs — ignore.
            }

            $image->setImageFormat('jpeg');
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->stripImage();

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $maxEdge = 1920;
            if (max($width, $height) > $maxEdge) {
                $image->thumbnailImage(
                    $width >= $height ? $maxEdge : 0,
                    $height > $width ? $maxEdge : 0,
                    true
                );
            }

            $qualities = [85, 75, 65, 55, 45, 35, 25];
            $bestUnderMax = null;

            foreach ([1.0, 0.85, 0.7, 0.55] as $scale) {
                $work = clone $image;
                if ($scale < 1.0) {
                    $w = max(1, (int) round($work->getImageWidth() * $scale));
                    $h = max(1, (int) round($work->getImageHeight() * $scale));
                    $work->resizeImage($w, $h, \Imagick::FILTER_LANCZOS, 1);
                }

                foreach ($qualities as $quality) {
                    $frame = clone $work;
                    $frame->setImageCompressionQuality($quality);
                    $binary = $frame->getImageBlob();
                    $frame->clear();
                    $frame->destroy();

                    $bytes = strlen($binary);
                    if ($bytes > self::MAX_BYTES) {
                        continue;
                    }

                    $candidate = [
                        'binary' => $binary,
                        'bytes' => $bytes,
                        'mime' => 'image/jpeg',
                        'width' => $work->getImageWidth(),
                        'height' => $work->getImageHeight(),
                        'was_resized' => true,
                    ];

                    if ($bytes >= self::MIN_BYTES) {
                        $work->clear();
                        $work->destroy();
                        $image->clear();
                        $image->destroy();

                        return $candidate;
                    }

                    if ($bestUnderMax === null || $bytes > $bestUnderMax['bytes']) {
                        $bestUnderMax = $candidate;
                    }

                    break;
                }

                $work->clear();
                $work->destroy();
                if ($bestUnderMax !== null) {
                    break;
                }
            }

            $image->clear();
            $image->destroy();

            return $bestUnderMax;
        } catch (\Throwable $e) {
            Log::warning('Imagick compress failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}|null
     */
    protected static function compressWithImageMagickCli(string $path): ?array
    {
        $bin = self::imageMagickBinary();
        if ($bin === null || ! is_readable($path) || ! function_exists('exec')) {
            return null;
        }

        $edges = [1920, 1600, 1280, 1024, 800, 640];
        $qualities = [85, 75, 65, 55, 45, 35];
        $bestUnderMax = null;

        foreach ($edges as $edge) {
            foreach ($qualities as $quality) {
                $target = self::tempDir().'/'.Str::uuid().'.jpg';
                if (! self::runImageMagickCommand($path, $target, $edge, $quality)) {
                    @unlink($target);
                    continue;
                }

                $bytes = (int) filesize($target);
                if ($bytes < 32 || self::detectFormat($target) !== 'jpeg' || $bytes > self::MAX_BYTES) {
                    @unlink($target);
                    continue;
                }

                $binary = (string) file_get_contents($target);
                $info = @getimagesize($target);
                @unlink($target);

                $candidate = [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => 'image/jpeg',
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'was_resized' => true,
                ];

                if ($candidate['bytes'] >= self::MIN_BYTES) {
                    return $candidate;
                }

                if ($bestUnderMax === null || $candidate['bytes'] > $bestUnderMax['bytes']) {
                    $bestUnderMax = $candidate;
                }

                break 2;
            }
        }

        return $bestUnderMax;
    }

    protected static function runImageMagickCommand(string $input, string $output, int $maxEdge, int $quality): bool
    {
        $bin = self::imageMagickBinary();
        if ($bin === null) {
            return false;
        }

        // `magick input -resize ... output` (IM7) or `convert input ... output` (IM6)
        $isMagick = str_ends_with($bin, 'magick');
        $cmd = escapeshellarg($bin);
        if ($isMagick) {
            $cmd .= ' '.escapeshellarg($input);
        } else {
            $cmd .= ' '.escapeshellarg($input);
        }

        $cmd .= ' -auto-orient'
            .' -resize '.escapeshellarg($maxEdge.'x'.$maxEdge.'>')
            .' -quality '.escapeshellarg((string) $quality)
            .' -strip'
            .' '.escapeshellarg($output)
            .' 2>&1';

        $lines = [];
        $code = 1;
        @exec($cmd, $lines, $code);

        return is_file($output) && filesize($output) > 32 && self::detectFormat($output) === 'jpeg';
    }

    protected static function forceJpegViaCli(string $path): ?string
    {
        $target = self::tempDir().'/'.Str::uuid().'.jpg';

        if (self::runImageMagickCommand($path, $target, 1920, 80)) {
            return $target;
        }

        if (self::runSipsCommand($path, $target, 1920, 80)) {
            return $target;
        }

        @unlink($target);

        return null;
    }

    protected static function imageMagickBinary(): ?string
    {
        static $cached;
        if ($cached !== null) {
            return $cached ?: null;
        }

        $candidates = [
            '/usr/bin/magick',
            '/usr/local/bin/magick',
            '/usr/bin/convert',
            '/usr/local/bin/convert',
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                $cached = $candidate;

                return $cached;
            }
        }

        if (function_exists('shell_exec')) {
            foreach (['magick', 'convert'] as $name) {
                $path = trim((string) @shell_exec('command -v '.escapeshellarg($name).' 2>/dev/null'));
                if ($path !== '' && is_executable($path)) {
                    $cached = $path;

                    return $cached;
                }
            }
        }

        $cached = '';

        return null;
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}|null
     */
    protected static function compressWithSips(string $path): ?array
    {
        if (self::sipsBinary() === null || ! is_readable($path)) {
            return null;
        }

        $edges = [1920, 1600, 1400, 1280, 1024, 900, 800, 640];
        $qualities = [85, 75, 65, 55, 45, 35, 25];
        $bestUnderMax = null;

        foreach ($edges as $edge) {
            foreach ($qualities as $quality) {
                $target = self::tempDir().'/'.Str::uuid().'.jpg';

                // Try with quality option, then without (older macOS).
                $ok = self::runSipsCommand($path, $target, $edge, $quality)
                    || self::runSipsCommand($path, $target, $edge, null);

                if (! $ok) {
                    @unlink($target);
                    continue;
                }

                $bytes = (int) filesize($target);
                if ($bytes < 32 || self::detectFormat($target) !== 'jpeg') {
                    @unlink($target);
                    continue;
                }

                if ($bytes > self::MAX_BYTES) {
                    @unlink($target);
                    continue;
                }

                $binary = (string) file_get_contents($target);
                $info = @getimagesize($target);
                @unlink($target);

                $candidate = [
                    'binary' => $binary,
                    'bytes' => strlen($binary),
                    'mime' => 'image/jpeg',
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'was_resized' => true,
                ];

                // Prefer landing in the 400–700KB band.
                if ($candidate['bytes'] >= self::MIN_BYTES) {
                    return $candidate;
                }

                // Keep the largest under-max result under 400KB as fallback.
                if ($bestUnderMax === null || $candidate['bytes'] > $bestUnderMax['bytes']) {
                    $bestUnderMax = $candidate;
                }

                // No need to shrink further once we have something under max.
                break 2;
            }
        }

        return $bestUnderMax;
    }

    protected static function runSipsCommand(string $input, string $output, int $maxEdge, ?int $quality): bool
    {
        $sips = self::sipsBinary();
        if ($sips === null) {
            return false;
        }

        $cmd = escapeshellarg($sips)
            .' -s format jpeg'
            .' --resampleHeightWidthMax '.escapeshellarg((string) $maxEdge);

        if ($quality !== null) {
            $cmd .= ' -s formatOptions '.escapeshellarg((string) $quality);
        }

        $cmd .= ' '.escapeshellarg($input).' --out '.escapeshellarg($output).' 2>&1';

        $outputLines = [];
        $code = 1;
        if (function_exists('exec')) {
            @exec($cmd, $outputLines, $code);
        }

        // Accept a valid JPEG even if sips returned a non-zero status (macOS warnings).
        return is_file($output) && filesize($output) > 32 && self::detectFormat($output) === 'jpeg';
    }

    /**
     * @return array{binary: string, bytes: int, mime: string, width: int|null, height: int|null, was_resized: bool}|null
     */
    protected static function compressWithGd(string $path): ?array
    {
        self::raiseMemoryLimit();
        @ini_set('memory_limit', '-1');

        $image = self::loadGdImage($path);
        if (! $image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = 1.0;
        if (max($width, $height) > 1920) {
            $scale = 1920 / max($width, $height);
        }

        $quality = 85;
        $bestUnderMax = null;
        $binary = '';
        $newW = $width;
        $newH = $height;

        for ($attempt = 0; $attempt < 24; $attempt++) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $canvas = imagecreatetruecolor($newW, $newH);
            if ($canvas === false) {
                break;
            }

            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);

            ob_start();
            imagejpeg($canvas, null, $quality);
            $binary = (string) ob_get_clean();
            imagedestroy($canvas);

            $bytes = strlen($binary);
            if ($bytes <= self::MAX_BYTES) {
                $candidate = [
                    'binary' => $binary,
                    'bytes' => $bytes,
                    'mime' => 'image/jpeg',
                    'width' => $newW,
                    'height' => $newH,
                    'was_resized' => true,
                ];

                if ($bytes >= self::MIN_BYTES) {
                    imagedestroy($image);

                    return $candidate;
                }

                if ($bestUnderMax === null || $bytes > $bestUnderMax['bytes']) {
                    $bestUnderMax = $candidate;
                }

                // Already under max; stop shrinking.
                break;
            }

            if ($quality > 40) {
                $quality -= 7;
            } else {
                $scale *= 0.82;
                $quality = 75;
            }
        }

        imagedestroy($image);

        return $bestUnderMax;
    }

    protected static function sipsBinary(): ?string
    {
        static $cached;
        if ($cached !== null) {
            return $cached ?: null;
        }

        foreach (['/usr/bin/sips', '/bin/sips'] as $candidate) {
            if (is_executable($candidate)) {
                $cached = $candidate;

                return $cached;
            }
        }

        if (function_exists('shell_exec')) {
            $path = trim((string) @shell_exec('command -v sips 2>/dev/null'));
            if ($path !== '' && is_executable($path)) {
                $cached = $path;

                return $cached;
            }
        }

        $cached = '';

        return null;
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
        if (str_starts_with($bytes, "II*\x00") || str_starts_with($bytes, "MM\x00*")) {
            return 'tiff';
        }

        if (strlen($bytes) >= 12 && substr($bytes, 4, 4) === 'ftyp') {
            $brand = strtolower(substr($bytes, 8, 4));
            if (in_array($brand, ['heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1', 'heim', 'heis'], true)) {
                return 'heic';
            }
            if ($brand === 'heif') {
                return 'heif';
            }
            if (in_array($brand, ['avif', 'avis'], true)) {
                return 'avif';
            }
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
        @ini_set('memory_limit', '1024M');
        @ini_set('memory_limit', '-1');
    }

    /** @return \GdImage|resource|null */
    protected static function loadGdImage(string $path)
    {
        if (! is_readable($path)) {
            return null;
        }

        self::raiseMemoryLimit();

        $info = @getimagesize($path);
        $detectedType = is_array($info) ? (int) ($info[2] ?? 0) : 0;

        $loaders = [];
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
                // try next
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
