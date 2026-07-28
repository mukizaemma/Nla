<?php

namespace App\Livewire\Admin\Shared;

use App\Models\MediaAsset;
use App\Support\AdminImageUploader;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImageField extends Component
{
    use WithFileUploads;

    #[Modelable]
    public ?string $value = null;

    public string $folder = 'uploads';

    public string $label = 'Image';

    public bool $allowSmall = false;

    public ?string $source = null;

    public $upload = null;

    public bool $showLibrary = false;

    public string $librarySearch = '';

    public ?string $sizeMessage = null;

    public ?int $previewOriginalBytes = null;

    public ?int $previewFinalBytes = null;

    public bool $previewAllowed = true;

    public function updatedUpload(): void
    {
        $this->resetErrorBag('upload');
        $this->sizeMessage = null;
        $this->previewOriginalBytes = null;
        $this->previewFinalBytes = null;
        $this->previewAllowed = true;

        if (! $this->upload) {
            return;
        }

        $this->validate([
            'upload' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,bmp',
                'mimetypes:image/jpeg,image/pjpeg,image/png,image/gif,image/webp,image/bmp,image/x-ms-bmp',
                'max:'.AdminImageUploader::ABSOLUTE_UPLOAD_MAX_KB,
            ],
        ], [
            'upload.mimes' => 'Please upload a JPG, JPEG, PNG, WEBP, GIF, or BMP image.',
            'upload.mimetypes' => 'Please upload a JPG, JPEG, PNG, WEBP, GIF, or BMP image.',
        ]);

        $preview = AdminImageUploader::preview($this->upload, $this->allowSmall);
        $this->previewOriginalBytes = $preview['original_bytes'];
        $this->previewFinalBytes = $preview['final_bytes'];
        $this->previewAllowed = $preview['allowed'];
        $this->sizeMessage = $preview['message'];

        if (! $preview['allowed']) {
            $this->addError('upload', $preview['message']);
            $this->upload = null;

            return;
        }

        try {
            $result = AdminImageUploader::store(
                $this->upload,
                $this->folder,
                $this->allowSmall,
                $this->source ?: $this->folder,
            );
            $this->value = $result['path'];
            $this->sizeMessage = ($result['reused'] ?? false)
                ? 'Reused existing library image — '.AdminImageUploader::formatBytes($result['bytes'])
                : (
                    ($result['was_resized'] ? 'Resized & saved — ' : 'Saved — ')
                    .AdminImageUploader::formatBytes($result['bytes'])
                    .($result['was_resized'] ? ' (was larger than 700KB)' : '')
                );
            $this->previewFinalBytes = $result['bytes'];
            $this->upload = null;
            $this->showLibrary = false;
        } catch (\Throwable $e) {
            $this->addError('upload', $e->getMessage());
            $this->upload = null;
        }
    }

    public function openLibrary(): void
    {
        $this->showLibrary = true;
        $this->librarySearch = '';
    }

    public function closeLibrary(): void
    {
        $this->showLibrary = false;
    }

    public function selectMedia(int $id): void
    {
        $media = MediaAsset::find($id);
        if (! $media) {
            return;
        }

        $this->value = $media->path;
        $this->sizeMessage = 'Selected from library — '.$media->formatted_bytes;
        $this->previewFinalBytes = (int) $media->bytes;
        $this->previewOriginalBytes = (int) $media->bytes;
        $this->previewAllowed = true;
        $this->upload = null;
        $this->showLibrary = false;
        $this->resetErrorBag('upload');
    }

    public function clearImage(): void
    {
        $this->value = null;
        $this->upload = null;
        $this->sizeMessage = null;
        $this->previewOriginalBytes = null;
        $this->previewFinalBytes = null;
        $this->resetErrorBag('upload');
    }

    public function render()
    {
        $library = collect();
        if ($this->showLibrary) {
            $query = MediaAsset::query()->orderByDesc('created_at');
            if ($this->librarySearch !== '') {
                $term = '%'.$this->librarySearch.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('original_name', 'like', $term)
                        ->orWhere('folder', 'like', $term)
                        ->orWhere('path', 'like', $term)
                        ->orWhere('source', 'like', $term);
                });
            }
            $library = $query->limit(48)->get();
        }

        return view('livewire.admin.shared.image-field', [
            'library' => $library,
        ]);
    }
}
