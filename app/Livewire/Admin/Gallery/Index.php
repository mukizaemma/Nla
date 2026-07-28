<?php

namespace App\Livewire\Admin\Gallery;

use App\Models\MediaAsset;
use App\Models\MediaGalleryItem;
use App\Support\AdminImageUploader;
use App\Support\MediaLibrarySync;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $tab = 'library';

    public string $search = '';

    public string $typeFilter = '';

    public ?int $editingId = null;

    public bool $showFormModal = false;

    public string $type = 'image';

    public ?string $image_path = null;

    public ?string $video_url = null;

    public ?string $title = null;

    public ?string $caption = null;

    public bool $is_featured = false;

    public bool $is_active = true;

    public ?int $sort_order = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['library', 'public'], true) ? $tab : 'library';
        $this->resetPage();
    }

    public function syncLibrary(): void
    {
        $result = MediaLibrarySync::syncAndDeduplicate();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Library synced',
            'text' => "Registered {$result['registered']} new image(s). Removed {$result['duplicates_removed']} duplicate file(s).",
            'timer' => 4500,
        ]);
    }

    public function removeDuplicateFiles(): void
    {
        $result = MediaLibrarySync::syncAndDeduplicate();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Duplicates cleaned',
            'text' => "Removed {$result['duplicates_removed']} duplicate file(s) from storage.",
            'timer' => 4000,
        ]);
    }

    public function deleteAsset(int $id): void
    {
        $asset = MediaAsset::findOrFail($id);
        $path = $asset->path;
        $asset->deleteFileAndRecord();

        // Also detach from public gallery items that pointed at this file.
        MediaGalleryItem::query()
            ->where('image_path', $path)
            ->update(['image_path' => null, 'is_active' => false]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Image removed',
            'text' => 'The image was deleted from the library.',
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
        $this->tab = 'public';
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $g = MediaGalleryItem::findOrFail($id);
        $this->editingId = $g->id;
        $this->type = $g->type;
        $this->image_path = $g->image_path;
        $this->video_url = $g->video_url ?? '';
        $this->title = $g->title ?? '';
        $this->caption = $g->caption ?? '';
        $this->is_featured = (bool) $g->is_featured;
        $this->is_active = (bool) $g->is_active;
        $this->sort_order = $g->sort_order;
        $this->showFormModal = true;
        $this->tab = 'public';
    }

    public function save(): void
    {
        $rules = [
            'type' => ['required', 'in:image,video'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'image_path' => [$this->type === 'image' ? 'required' : 'nullable', 'string', 'max:500'],
        ];
        $data = $this->validate($rules);

        $payload = [
            'type' => $data['type'],
            'title' => $data['title'] ?: null,
            'caption' => $data['caption'] ?: null,
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? 0,
            'image_path' => $data['type'] === 'image' ? ($data['image_path'] ?? null) : null,
            'video_url' => $data['type'] === 'video' ? ($data['video_url'] ?? null) : null,
        ];

        if ($payload['type'] === 'image' && $payload['image_path']) {
            AdminImageUploader::registerExisting($payload['image_path'], 'gallery', 'gallery');
        }

        if ($this->editingId) {
            MediaGalleryItem::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Gallery item updated.');
        } else {
            MediaGalleryItem::create($payload);
            session()->flash('success', 'Gallery item created.');
        }

        $this->closeFormModal();
    }

    public function delete(int $id): void
    {
        MediaGalleryItem::findOrFail($id)->delete();
        session()->flash('success', 'Gallery item deleted.');
        $this->closeFormModal();
    }

    protected function resetForm(): void
    {
        $this->type = 'image';
        $this->image_path = null;
        $this->video_url = null;
        $this->title = null;
        $this->caption = null;
        $this->is_featured = false;
        $this->is_active = true;
        $this->sort_order = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $library = null;
        $publicItems = null;

        if ($this->tab === 'library') {
            $query = MediaAsset::query()->orderByDesc('created_at');
            if ($this->search !== '') {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('original_name', 'like', $term)
                        ->orWhere('folder', 'like', $term)
                        ->orWhere('path', 'like', $term)
                        ->orWhere('source', 'like', $term);
                });
            }
            $library = $query->paginate(24);
        } else {
            $query = MediaGalleryItem::query()->orderBy('sort_order')->orderByDesc('id');
            if ($this->search !== '') {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)->orWhere('caption', 'like', $term);
                });
            }
            if ($this->typeFilter !== '') {
                $query->where('type', $this->typeFilter);
            }
            $publicItems = $query->paginate(15);
        }

        return view('livewire.admin.gallery.index', [
            'library' => $library,
            'publicItems' => $publicItems,
            'libraryCount' => MediaAsset::count(),
        ]);
    }
}
