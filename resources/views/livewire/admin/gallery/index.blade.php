<div>
    <div class="bg-light rounded p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1"><i class="fa fa-images me-2 text-primary"></i>Media &amp; Gallery</h4>
                <p class="text-muted small mb-0">
                    All admin uploads appear in the library ({{ $libraryCount }}). Use them anywhere, or publish selected ones to the public gallery.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="syncLibrary" wire:loading.attr="disabled">
                    <i class="fa fa-sync me-1"></i> Sync &amp; remove duplicates
                </button>
                @if($tab === 'public')
                    <button type="button" class="btn btn-primary btn-sm" wire:click="create">
                        <i class="fa fa-plus me-1"></i> Add public item
                    </button>
                @endif
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button type="button" class="nav-link {{ $tab === 'library' ? 'active' : '' }}" wire:click="setTab('library')">
                    All uploaded images
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link {{ $tab === 'public' ? 'active' : '' }}" wire:click="setTab('public')">
                    Public gallery items
                </button>
            </li>
        </ul>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <input
                type="search"
                class="form-control form-control-sm"
                style="max-width: 280px;"
                placeholder="Search…"
                wire:model.live.debounce.300ms="search"
            >
            @if($tab === 'public')
                <select class="form-select form-select-sm" style="max-width: 140px;" wire:model.live="typeFilter">
                    <option value="">All types</option>
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                </select>
            @endif
        </div>

        @if($tab === 'library')
            @if($library->isEmpty())
                <div class="p-4 text-center text-muted border rounded bg-white">
                    No images in the library yet.
                    <button type="button" class="btn btn-link btn-sm" wire:click="syncLibrary">Scan existing uploads</button>
                </div>
            @else
                <div class="row g-3">
                    @foreach($library as $media)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ $media->url }}" class="card-img-top" alt="" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-2">
                                    <div class="small text-truncate fw-semibold">{{ $media->original_name ?: basename($media->path) }}</div>
                                    <div class="small text-muted">{{ $media->formatted_bytes }} · {{ $media->folder }}</div>
                                    @if($media->width && $media->height)
                                        <div class="small text-muted">{{ $media->width }}×{{ $media->height }}</div>
                                    @endif
                                </div>
                                <div class="card-footer bg-white border-0 p-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger w-100"
                                        wire:click="deleteAsset({{ $media->id }})"
                                        wire:confirm="Delete this image from storage? Forms still using it will lose the image."
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">{{ $library->links() }}</div>
            @endif
        @else
            @if($publicItems->isEmpty())
                <div class="p-4 text-center text-muted border rounded bg-white">No public gallery items yet.</div>
            @else
                <div class="table-responsive bg-white rounded shadow-sm">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($publicItems as $item)
                                <tr>
                                    <td style="width: 80px;">
                                        @if($item->type === 'image' && $item->image_path)
                                            <img src="{{ asset($item->image_path) }}" alt="" style="height: 48px; width: 64px; object-fit: cover;" class="rounded">
                                        @else
                                            <span class="badge bg-secondary">Video</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->title ?: '—' }}</td>
                                    <td class="text-capitalize">{{ $item->type }}</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Hidden</span>
                                        @endif
                                        @if($item->is_featured)
                                            <span class="badge bg-warning text-dark">Featured</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="edit({{ $item->id }})">Edit</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $item->id }})" wire:confirm="Delete this public gallery item?">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $publicItems->links() }}</div>
            @endif
        @endif
    </div>

    @if($showFormModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} public gallery item</h5>
                        <button type="button" class="btn-close" wire:click="closeFormModal"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" wire:model.live="type">
                                    <option value="image">Image</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>
                            @if($type === 'image')
                                <div class="mb-3">
                                    <livewire:admin.shared.image-field
                                        wire:model="image_path"
                                        folder="gallery"
                                        label="Image"
                                        source="gallery"
                                        :key="'gallery-img-'.($editingId ?? 'new')"
                                    />
                                    @error('image_path') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label">Video URL</label>
                                    <input type="url" class="form-control" wire:model="video_url" placeholder="https://…">
                                    @error('video_url') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" wire:model="title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Caption</label>
                                <textarea class="form-control" rows="2" wire:model="caption"></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Sort order</label>
                                    <input type="number" class="form-control" wire:model="sort_order" min="0">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="is_active" id="galActive">
                                        <label class="form-check-label" for="galActive">Active</label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="is_featured" id="galFeat">
                                        <label class="form-check-label" for="galFeat">Featured</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeFormModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
