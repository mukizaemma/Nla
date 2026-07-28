<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaLibrarySync
{
    /** @var list<string> */
    public static array $folders = [
        'logos',
        'hero',
        'cta',
        'headers',
        'sliders',
        'facilities',
        'school-activities',
        'school-activity-gallery',
        'gallery',
        'departments',
        'departments/gallery',
        'services',
        'services/gallery',
        'leadership',
        'partners',
        'editor-images',
        'doctors',
    ];

    /**
     * Scan public storage image folders and register missing assets.
     * Also remove on-disk duplicate files that share the same content hash.
     *
     * @return array{registered: int, duplicates_removed: int}
     */
    public static function syncAndDeduplicate(): array
    {
        $registered = 0;
        $duplicatesRemoved = 0;
        $seenHashes = [];

        foreach (self::$folders as $folder) {
            $absolute = Storage::disk('public')->path($folder);
            if (! is_dir($absolute)) {
                continue;
            }

            $files = File::allFiles($absolute);
            foreach ($files as $file) {
                if (! preg_match('/\.(jpe?g|png|gif|webp)$/i', $file->getFilename())) {
                    continue;
                }

                $diskPath = ltrim(str_replace(Storage::disk('public')->path(''), '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $diskPath = str_replace('\\', '/', $diskPath);
                $publicPath = 'storage/'.$diskPath;
                $binary = File::get($file->getPathname());
                $hash = hash('sha256', $binary);

                if (isset($seenHashes[$hash])) {
                    // Duplicate file on disk — delete this copy, keep the first.
                    File::delete($file->getPathname());
                    $duplicatesRemoved++;
                    continue;
                }

                $seenHashes[$hash] = $publicPath;

                $existing = MediaAsset::query()->where('hash', $hash)->first();
                if ($existing) {
                    continue;
                }

                $info = @getimagesize($file->getPathname());
                $mime = is_array($info) ? ($info['mime'] ?? null) : null;
                $mime = $mime ?: (function_exists('mime_content_type') ? @mime_content_type($file->getPathname()) : 'image/jpeg');

                MediaAsset::query()->create([
                    'path' => $publicPath,
                    'disk_path' => $diskPath,
                    'folder' => $folder,
                    'original_name' => $file->getFilename(),
                    'mime' => $mime,
                    'bytes' => $file->getSize(),
                    'width' => is_array($info) ? ($info[0] ?? null) : null,
                    'height' => is_array($info) ? ($info[1] ?? null) : null,
                    'hash' => $hash,
                    'source' => 'sync',
                ]);
                $registered++;
            }
        }

        return [
            'registered' => $registered,
            'duplicates_removed' => $duplicatesRemoved,
        ];
    }
}
