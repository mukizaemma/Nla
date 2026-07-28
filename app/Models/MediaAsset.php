<?php

namespace App\Models;

use App\Support\AdminImageUploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'path',
        'disk_path',
        'folder',
        'original_name',
        'mime',
        'bytes',
        'width',
        'height',
        'hash',
        'source',
    ];

    protected $casts = [
        'bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function getFormattedBytesAttribute(): string
    {
        return AdminImageUploader::formatBytes((int) $this->bytes);
    }

    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }

    public function deleteFileAndRecord(): void
    {
        if ($this->disk_path && Storage::disk('public')->exists($this->disk_path)) {
            Storage::disk('public')->delete($this->disk_path);
        }
        $this->delete();
    }

    /**
     * Find duplicate groups by hash (more than one path with same binary is rare
     * because we unique by hash — so duplicates = same hash reused OR same basename
     * across folders). We treat identical hash as one asset; "duplicates" here means
     * multiple MediaAsset rows that point to files with the same visual content
     * registered under different hashes incorrectly, OR same path referenced.
     *
     * Practical duplicate cleanup: identical file bytes (hash) should be one row.
     * Also flag same original_name + same bytes in different folders.
     */
    public static function duplicateGroups()
    {
        return static::query()
            ->select('hash')
            ->groupBy('hash')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('hash');
    }
}
