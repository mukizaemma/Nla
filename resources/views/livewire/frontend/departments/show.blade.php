<div>
    @php
        $diagnosticTestUrl = 'https://www.acediagnostictest.com/diagnostictest/?route=common/pages&page_identifier=diagnostictest';
    @endphp

    @if($department->cover_image)
        <div class="dept-cover-outer">
            <div class="dept-cover">
                <img src="{{ asset($department->cover_image) }}" alt="{{ $department->name }}" class="dept-cover__img">
            </div>
        </div>
    @endif

    <div class="content page-wrap">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" wire:navigate>Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('departments.index') }}" wire:navigate>Programs</a>
            <span aria-hidden="true">/</span>
            <span>{{ $department->name }}</span>
        </nav>

        <article class="department-single">
            <header class="dept-header">
                <p class="section-heading">Program</p>
                <h1 class="dept-title">{{ $department->name }}</h1>
                @if($department->description)
                    <div class="dept-description prose">{!! $department->description !!}</div>
                @endif
            </header>

            @if(!empty($gallery))
                <section class="gallery-section" aria-labelledby="program-gallery-title">
                    <h2 id="program-gallery-title" class="section-heading">Gallery</h2>
                    <div class="gallery-grid">
                        @foreach($gallery as $path)
                            <a href="{{ asset($path) }}" target="_blank" rel="noopener noreferrer" class="gallery-item">
                                <img src="{{ asset($path) }}" alt="{{ $department->name }} gallery" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="dept-cta">
                <h2 class="dept-cta__title">Ready to take the next step?</h2>
                <p class="dept-cta__text">Register your child for {{ $department->name }}, or take the free ACE Diagnostic Test to find the right starting level.</p>
                <div class="dept-cta__actions">
                    <a href="{{ route('appointment') }}" class="btn btn--dark" wire:navigate>Register</a>
                    <a href="{{ $diagnosticTestUrl }}" class="btn btn--outline" target="_blank" rel="noopener noreferrer">Take Diagnostic Test</a>
                    <a href="{{ route('departments.index') }}" class="btn btn--ghost-dark" wire:navigate>All programs</a>
                </div>
            </div>
        </article>
    </div>

    <style>
        .department-single { padding: 8px 0 48px; }
        .dept-cover-outer {
            width: 100vw;
            max-width: 100vw;
            position: relative;
            left: 50%;
            margin-left: -50vw;
            height: min(70vh, 560px);
            min-height: 320px;
            overflow: hidden;
            margin-bottom: 0;
        }
        .dept-cover { width: 100%; height: 100%; position: relative; overflow: hidden; }
        .dept-cover__img,
        .dept-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            animation: dept-zoom-in 5s ease-out forwards;
        }
        @keyframes dept-zoom-in {
            from { transform: scale(1); }
            to { transform: scale(1.06); }
        }
        .dept-header { margin-bottom: 32px; }
        .dept-title {
            font-family: var(--serif, Georgia, serif);
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 700;
            margin: 8px 0 20px;
            color: var(--slate-dark, #1e293b);
        }
        .dept-description {
            font-size: 1rem;
            line-height: 1.75;
            color: var(--gray-700, #334155);
            max-width: 48rem;
        }
        .dept-description h3 { margin-top: 1.5em; margin-bottom: .5em; font-size: 1.125rem; }
        .dept-description ul { margin: .75em 0 1em 1.25em; }
        .dept-description li { margin-bottom: .35em; }
        .gallery-section { margin: 40px 0; }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .gallery-item {
            display: block;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
        }
        .gallery-item img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; transition: transform .3s; }
        .gallery-item:hover img { transform: scale(1.04); }
        .dept-cta {
            margin-top: 48px;
            padding: 32px;
            border-radius: 12px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            border: 1px solid #e2e8f0;
        }
        .dept-cta__title {
            font-family: var(--serif, Georgia, serif);
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: var(--slate-dark, #1e293b);
        }
        .dept-cta__text { color: #64748b; margin-bottom: 20px; max-width: 40rem; }
        .dept-cta__actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .btn--ghost-dark {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: .875rem;
            color: var(--slate-dark, #1e293b); text-decoration: none;
            border: 1px solid transparent;
        }
        .btn--ghost-dark:hover { background: #e2e8f0; }
    </style>
</div>
