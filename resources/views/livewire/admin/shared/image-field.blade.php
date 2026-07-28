<div class="admin-image-field">
    <label class="form-label">{{ $label }}</label>

    <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openLibrary">
            <i class="fa fa-images me-1"></i> Choose existing
        </button>
        <label class="btn btn-sm btn-outline-secondary mb-0">
            <i class="fa fa-upload me-1"></i> Upload new
            <input type="file" class="d-none" accept=".jpg,.jpeg,.JPG,.JPEG,.png,.PNG,.webp,.gif,.bmp,image/jpeg,image/png,image/webp,image/gif,image/bmp" wire:model="upload">
        </label>
        @if($value)
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearImage">
                <i class="fa fa-times me-1"></i> Clear
            </button>
        @endif
    </div>

    <div wire:loading wire:target="upload" class="small text-muted mb-2">
        Processing image…
    </div>

    @error('upload')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror

    @if($sizeMessage)
        <div class="alert alert-{{ $previewAllowed ? 'info' : 'warning' }} py-2 px-3 small mb-2">
            {{ $sizeMessage }}
            @if($previewOriginalBytes && $previewFinalBytes && $previewOriginalBytes !== $previewFinalBytes)
                <br>
                <span class="text-muted">
                    Original {{ \App\Support\AdminImageUploader::formatBytes($previewOriginalBytes) }}
                    → upload {{ \App\Support\AdminImageUploader::formatBytes($previewFinalBytes) }}
                </span>
            @elseif($previewFinalBytes)
                <br><span class="text-muted">Upload size: {{ \App\Support\AdminImageUploader::formatBytes($previewFinalBytes) }}</span>
            @endif
        </div>
    @endif

    <p class="small text-muted mb-2">
        @if($allowSmall)
            Logos may be under 400KB. Larger images are auto-resized to 400–700KB.
        @else
            Minimum 400KB · auto-resize to 400–700KB. JPG/JPEG, PNG, WEBP, GIF, and BMP are accepted.
        @endif
    </p>

    @if($value)
        <div class="border rounded p-2 bg-light d-inline-block">
            <img src="{{ asset($value) }}" alt="Selected" style="max-height: 140px; max-width: 240px; object-fit: contain;">
            <div class="small text-muted mt-1 text-break" style="max-width: 240px;">{{ $value }}</div>
        </div>
    @endif

    @if($showLibrary)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45);" wire:keydown.escape.window="closeLibrary">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Media library</h5>
                        <button type="button" class="btn-close" wire:click="closeLibrary"></button>
                    </div>
                    <div class="modal-body">
                        <input
                            type="search"
                            class="form-control mb-3"
                            placeholder="Search by name, folder…"
                            wire:model.live.debounce.300ms="librarySearch"
                        >
                        @if($library->isEmpty())
                            <p class="text-muted mb-0">No images in the library yet. Upload one to get started.</p>
                        @else
                            <div class="row g-2">
                                @foreach($library as $media)
                                    <div class="col-6 col-md-3">
                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 h-100 p-2 text-start"
                                            wire:click="selectMedia({{ $media->id }})"
                                        >
                                            <img src="{{ $media->url }}" alt="" class="img-fluid rounded mb-1" style="height: 90px; width: 100%; object-fit: cover;">
                                            <div class="small text-truncate">{{ $media->original_name ?: basename($media->path) }}</div>
                                            <div class="small text-muted">{{ $media->formatted_bytes }} · {{ $media->folder }}</div>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeLibrary">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
