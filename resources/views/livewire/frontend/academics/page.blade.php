<div>
    <x-page-locator :title="$content['title'] ?? $breadcrumb" :header="$header" />
    <div class="content page-wrap">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" wire:navigate>Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('departments.index') }}" wire:navigate>Academics</a>
            <span aria-hidden="true">/</span>
            <span>{{ $breadcrumb }}</span>
        </nav>
        <article class="static-page">
            @if(!empty($content['intro']))
                <p class="static-page__intro lead">{{ $content['intro'] }}</p>
            @endif
            @if(!empty($content['body']))
                <div class="static-page__body prose">{!! $content['body'] !!}</div>
            @endif
            <div class="static-page__actions">
                <a href="{{ route('appointment') }}" class="btn btn--primary" wire:navigate>Register</a>
                <a href="https://www.acediagnostictest.com/diagnostictest/?route=common/pages&page_identifier=diagnostictest" class="btn btn--outline" target="_blank" rel="noopener noreferrer">Take Diagnostic Test</a>
            </div>
        </article>
    </div>
</div>
