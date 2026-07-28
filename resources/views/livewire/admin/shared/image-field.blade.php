<div
    class="admin-image-field"
    x-data="nlaImageField({
        maxBytes: {{ \App\Support\AdminImageUploader::MAX_BYTES }},
        minBytes: {{ $allowSmall ? 0 : \App\Support\AdminImageUploader::MIN_BYTES }},
        allowSmall: {{ $allowSmall ? 'true' : 'false' }}
    })"
>
    <label class="form-label">{{ $label }}</label>

    <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openLibrary">
            <i class="fa fa-images me-1"></i> Choose existing
        </button>
        <label class="btn btn-sm btn-outline-secondary mb-0" :class="{ 'disabled': compressing }">
            <i class="fa fa-upload me-1"></i>
            <span x-text="compressing ? 'Compressing…' : 'Upload new'"></span>
            <input
                type="file"
                class="d-none"
                accept=".jpg,.jpeg,.JPG,.JPEG,.png,.PNG,.webp,.gif,.bmp,image/jpeg,image/png,image/webp,image/gif,image/bmp"
                :disabled="compressing"
                @change="onFileSelected($event)"
            >
        </label>
        @if($value)
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearImage">
                <i class="fa fa-times me-1"></i> Clear
            </button>
        @endif
    </div>

    <div wire:loading wire:target="upload" class="small text-muted mb-2">
        Saving image…
    </div>
    <div x-show="compressing" class="small text-muted mb-2" x-cloak>
        Resizing in browser to under 700KB…
    </div>
    <div x-show="clientError" class="text-danger small mb-2" x-text="clientError" x-cloak></div>

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
            Logos may be under 400KB. Larger images are auto-resized in your browser to under 700KB.
        @else
            Minimum 400KB source · auto-resized in your browser to under 700KB. JPG/JPEG, PNG, WEBP, GIF, and BMP are accepted.
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

@script
<script>
    if (!window.__nlaImageFieldDefined) {
        window.__nlaImageFieldDefined = true;
        Alpine.data('nlaImageField', (config) => ({
            maxBytes: config.maxBytes || (700 * 1024),
            minBytes: config.minBytes || 0,
            allowSmall: !!config.allowSmall,
            compressing: false,
            clientError: '',

            async onFileSelected(event) {
                const input = event.target;
                const file = input.files && input.files[0];
                input.value = '';
                this.clientError = '';

                if (!file) return;

                if (!file.type || !String(file.type).startsWith('image/')) {
                    this.clientError = 'Please choose an image file (JPG, PNG, WEBP, GIF, or BMP).';
                    return;
                }

                this.compressing = true;

                try {
                    let uploadFile = file;

                    if (file.size > this.maxBytes) {
                        uploadFile = await this.compressToJpeg(file, this.maxBytes, this.minBytes);
                    } else if (!this.allowSmall && file.size < this.minBytes) {
                        this.clientError = 'Image is too small. Please upload at least 400KB (logos may be smaller).';
                        return;
                    }

                    await this.uploadToLivewire(uploadFile);
                } catch (err) {
                    console.error(err);
                    this.clientError = (err && err.message)
                        ? err.message
                        : 'Could not resize this image in the browser. Try exporting it as JPG from Preview.';
                } finally {
                    this.compressing = false;
                }
            },

            uploadToLivewire(file) {
                return new Promise((resolve, reject) => {
                    this.$wire.upload(
                        'upload',
                        file,
                        () => resolve(),
                        (error) => reject(new Error(error || 'Upload failed.')),
                        () => {}
                    );
                });
            },

            async compressToJpeg(file, maxBytes, minBytes) {
                const bitmap = await this.loadBitmap(file);
                let width = bitmap.width;
                let height = bitmap.height;
                const maxEdge = 1920;
                const fit = Math.min(1, maxEdge / Math.max(width, height));
                width = Math.max(1, Math.round(width * fit));
                height = Math.max(1, Math.round(height * fit));

                const source = document.createElement('canvas');
                source.width = width;
                source.height = height;
                const sctx = source.getContext('2d', { alpha: false });
                sctx.fillStyle = '#ffffff';
                sctx.fillRect(0, 0, width, height);
                sctx.drawImage(bitmap, 0, 0, width, height);
                if (bitmap.close) bitmap.close();

                let quality = 0.92;
                let scale = 1;
                let best = null;

                for (let attempt = 0; attempt < 18; attempt++) {
                    const w = Math.max(1, Math.round(width * scale));
                    const h = Math.max(1, Math.round(height * scale));
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d', { alpha: false });
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(source, 0, 0, w, h);

                    const blob = await new Promise((resolve) => {
                        canvas.toBlob((b) => resolve(b), 'image/jpeg', quality);
                    });

                    if (!blob) {
                        throw new Error('Browser could not encode JPEG.');
                    }

                    if (blob.size <= maxBytes) {
                        best = blob;
                        if (!minBytes || blob.size >= minBytes) {
                            return this.blobToFile(blob, file.name);
                        }
                        break;
                    }

                    if (quality > 0.45) {
                        quality -= 0.07;
                    } else {
                        scale *= 0.82;
                        quality = 0.8;
                    }
                }

                if (!best || best.size > maxBytes) {
                    throw new Error('Could not compress this image under 700KB. Try a simpler photo.');
                }

                return this.blobToFile(best, file.name);
            },

            async loadBitmap(file) {
                if (window.createImageBitmap) {
                    try {
                        return await createImageBitmap(file);
                    } catch (e) {}
                }

                const url = URL.createObjectURL(file);
                try {
                    return await new Promise((resolve, reject) => {
                        const image = new Image();
                        image.onload = () => resolve(image);
                        image.onerror = () => reject(new Error(
                            'This image cannot be read in the browser. Export it as JPG from Preview and try again.'
                        ));
                        image.src = url;
                    });
                } finally {
                    URL.revokeObjectURL(url);
                }
            },

            blobToFile(blob, originalName) {
                const base = String(originalName || 'image').replace(/\.[^.]+$/, '');
                return new File([blob], base + '.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
            },
        }));
    }
</script>
@endscript
