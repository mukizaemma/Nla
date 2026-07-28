<div>
    @push('styles')
        <link href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}" rel="stylesheet">
        <link href="{{ asset('css/pages.css') }}?v={{ filemtime(public_path('css/pages.css')) }}" rel="stylesheet">
    @endpush

    @php
        $h = $siteContent['home'] ?? [];
        $heroPrimaryUrl = fn ($path) => str_starts_with((string) $path, 'http') ? $path : url($path);
        $hasSlides = $sliders->isNotEmpty();
        $slideList = $hasSlides ? $sliders : collect([(object)[
            'image_path' => optional($settings)->home_background_image_path,
            'title' => optional($settings)->company_name ?? 'New Life Leadership Academy',
            'caption' => 'Raising The Next Level Leaders For The Kingdom Of God',
            'button_text' => null,
            'button_url' => null,
        ]]);
        $slideCount = $slideList->count();
        $pillars = array_slice($h['curriculum_pillars'] ?? [], 0, 3);
        $diagnosticTestUrl = 'https://www.acediagnostictest.com/diagnostictest/?route=common/pages&page_identifier=diagnostictest';
        $programCards = ($programs ?? collect())->take(2);
        $activityEvents = $upcomingActivities->take(3);
        $activityNews = $upcomingActivities->skip(3)->take(3);
        if ($activityNews->isEmpty()) {
            $activityNews = $upcomingActivities->take(3);
        }
    @endphp

    {{-- Hero carousel --}}
    <div class="hero-slider"
         x-data="{
             current: 0, total: {{ $slideCount }},
             autoplayInterval: null,
             startAutoplay() { if (this.total <= 1) return; this.autoplayInterval = setInterval(() => { this.current = (this.current + 1) % this.total; }, 6000); },
             stopAutoplay() { if (this.autoplayInterval) clearInterval(this.autoplayInterval); },
             next() { this.current = (this.current + 1) % this.total; this.stopAutoplay(); this.startAutoplay(); },
             prev() { this.current = (this.current - 1 + this.total) % this.total; this.stopAutoplay(); this.startAutoplay(); }
         }"
         x-init="startAutoplay()"
         @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()">
        <div class="hero-slider__wrap">
            @foreach($slideList as $i => $slide)
                <div class="hero-slide" x-show="current === {{ $i }}"
                     x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="hero-slide__img-wrap">
                        @if($slide->image_path ?? null)
                            <img src="{{ asset($slide->image_path) }}" alt="" class="hero-slide__img" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                        @else
                            <div class="hero-slide__placeholder"></div>
                        @endif
                    </div>
                    <div class="hero-slide__overlay"></div>
                    <div class="hero-slide__content">
                        <h1 class="hero-slide__caption">{!! $slide->caption ?? $slide->title ?? optional($settings)->company_name !!}</h1>
                        @if($i === 0 && !empty($h['overview_fallback']))
                            <p class="hero-slide__desc">{{ $h['overview_fallback'] }}</p>
                        @endif
                        <div class="hero-slide__actions">
                            @php
                                $primaryText = $slide->button_text ?: ($h['hero_primary_text'] ?? 'Register');
                                $primaryLink = $slide->button_url ? $heroPrimaryUrl($slide->button_url) : $heroPrimaryUrl($h['hero_primary_url'] ?? '/appointment');
                                $secondaryText = $h['hero_secondary_text'] ?? 'Take Diagnostic Test';
                                $secondaryLink = $h['hero_secondary_url'] ?? $diagnosticTestUrl;
                                $secondaryIsExternal = str_starts_with((string) $secondaryLink, 'http');
                            @endphp
                            <a href="{{ $primaryLink }}" class="btn btn--dark" wire:navigate>{{ $primaryText }}</a>
                            <a href="{{ $secondaryIsExternal ? $secondaryLink : $heroPrimaryUrl($secondaryLink) }}"
                               class="btn btn--ghost"
                               @if($secondaryIsExternal) target="_blank" rel="noopener noreferrer" @else wire:navigate @endif>{{ $secondaryText }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($slideCount > 1)
            <button type="button" class="hero-slider__btn hero-slider__btn--prev" @click="prev()" aria-label="Previous slide">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button type="button" class="hero-slider__btn hero-slider__btn--next" @click="next()" aria-label="Next slide">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M9 18l6-6-6-6"/></svg>
            </button>
            <div class="hero-slider__dots">
                @foreach($slideList as $i => $slide)
                    <button type="button" class="hero-slider__dot" :class="{ 'hero-slider__dot--active': current === {{ $i }} }" @click="current = {{ $i }}; stopAutoplay(); startAutoplay();" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 1. Curriculum overview --}}
    <section class="home-curriculum-section" id="curriculum">
        <div class="content">
            <header class="home-section__head">
                <p class="section-heading">{{ $h['curriculum_label'] ?? 'Curriculum overview' }}</p>
                <h2 class="home-section__title">{{ $h['curriculum_subtitle'] ?? 'Raising The Next Level Leaders For The Kingdom Of God' }}</h2>
                @if(!empty($h['curriculum_intro']))
                    <p class="home-section__subtitle">{{ $h['curriculum_intro'] }}</p>
                @endif
            </header>
            @if(!empty($pillars))
                <div class="ace-pillars">
                    @foreach($pillars as $pillar)
                        <article class="ace-pillar">
                            <div class="ace-pillar__header">{{ $pillar['title'] ?? '' }}</div>
                            <div class="ace-pillar__body">
                                <p>{{ $pillar['description'] ?? '' }}</p>
                                <a href="{{ route('academics.about-ace') }}" class="ace-pillar__link" wire:navigate>{{ $h['curriculum_link_text'] ?? 'Read More' }} →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
            <div class="home-section__actions">
                <a href="{{ route('academics.about-ace') }}" class="btn btn--dark" wire:navigate>About ACE</a>
                <a href="{{ $diagnosticTestUrl }}" class="btn btn--outline" target="_blank" rel="noopener noreferrer">Take Diagnostic Test</a>
            </div>
        </div>
    </section>

    {{-- 2. Programs: Grade 7 & Grade 8 --}}
    <section class="home-programs" id="programs" aria-labelledby="programs-title">
        <div class="content">
            <header class="home-section__head">
                <p class="section-heading">{{ $h['programs_label'] ?? 'Programs' }}</p>
                <h2 id="programs-title" class="home-section__title">{{ $h['programs_title'] ?? 'Grade 7 & 8 Programs' }}</h2>
                @if(!empty($h['programs_subtitle']))
                    <p class="home-section__subtitle">{{ $h['programs_subtitle'] }}</p>
                @endif
            </header>

            <div class="programs-grid programs-grid--two home-programs__grid">
                @forelse($programCards as $i => $program)
                    @php $accent = $i % 3; @endphp
                    <article class="program-card program-card--{{ $accent }}">
                        <a href="{{ route('departments.show', ['department' => $program->slug ?: $program->id]) }}" class="program-card__img-wrap" wire:navigate>
                            @if($program->cover_image)
                                <img src="{{ asset($program->cover_image) }}" alt="{{ $program->name }}" class="program-card__img" loading="lazy">
                            @else
                                <div class="program-card__placeholder"></div>
                            @endif
                        </a>
                        <h3 class="program-card__title">{{ $program->name }}</h3>
                        <p class="program-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 140) ?: ($h['programs_card_fallback'] ?? '') }}</p>
                        <a href="{{ route('departments.show', ['department' => $program->slug ?: $program->id]) }}" class="program-card__btn" wire:navigate>
                            View more
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <span class="program-card__line" aria-hidden="true"></span>
                    </article>
                @empty
                    <p class="home-section__empty">Programs will appear here once added in Admin → Programs.</p>
                @endforelse
            </div>

            @if($programCards->isNotEmpty())
                <div class="home-section__actions">
                    <a href="{{ route('departments.index') }}" class="btn btn--outline" wire:navigate>{{ $h['programs_link_text'] ?? 'View all programs' }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- 3. Why choose us --}}
    <section class="home-why" id="why-choose-us" aria-labelledby="why-choose-title">
        <div class="content">
            <header class="home-section__head">
                <p class="section-heading">{{ $h['why_choose_label'] ?? 'Why choose us' }}</p>
                <h2 id="why-choose-title" class="home-section__title">{{ $h['why_choose_title'] ?? 'Why Choose Us' }}</h2>
            </header>

            @if(!empty($whyChooseCards))
                <div class="why-choose-grid">
                    @foreach($whyChooseCards as $card)
                        <article class="why-choose-card">
                            <h3 class="why-choose-card__title">{{ $card['name'] ?? '' }}</h3>
                            <div class="why-choose-card__text">{!! $card['description'] ?? '' !!}</div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="home-section__empty">{{ $h['why_choose_empty'] ?? 'Why Choose Us features will appear here once added in Admin.' }}</p>
            @endif
        </div>
    </section>

    {{-- 4. School facilities --}}
    <section class="home-facilities" id="facilities" aria-labelledby="facilities-title">
        <div class="content">
            <header class="home-section__head">
                <p class="section-heading">{{ $siteContent['facilities']['section_label'] ?? 'Our campus' }}</p>
                <h2 id="facilities-title" class="home-section__title">{{ $h['facilities_title'] ?? 'School Facilities' }}</h2>
                @if(!empty($siteContent['facilities']['section_intro']))
                    <p class="home-section__subtitle">{{ $siteContent['facilities']['section_intro'] }}</p>
                @endif
            </header>

            <div class="facilities-grid home-facilities__grid">
                @forelse($facilities as $facility)
                    <article class="facility-card">
                        <div class="facility-card__img-wrap">
                            @if($facility->image_path)
                                <img src="{{ asset($facility->image_path) }}" alt="{{ $facility->name }}" class="facility-card__img" loading="lazy">
                            @else
                                <div class="facility-card__placeholder"></div>
                            @endif
                        </div>
                        <h3 class="facility-card__title">{{ $facility->name }}</h3>
                        @if($facility->description)
                            <div class="facility-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($facility->description), 120) }}</div>
                        @endif
                    </article>
                @empty
                    <p class="home-section__empty">{{ $siteContent['facilities']['empty'] ?? 'Facilities will be listed here once added in Admin.' }}</p>
                @endforelse
            </div>

            <div class="home-section__actions">
                <a href="{{ route('facilities') }}" class="btn btn--dark" wire:navigate>View all facilities</a>
            </div>
        </div>
    </section>

    {{-- 5. School activities & latest news --}}
    <section class="home-news-events" id="activities" aria-labelledby="activities-title">
        <div class="content">
            <header class="home-section__head home-section__head--left">
                <p class="section-heading">{{ $siteContent['activities']['section_label'] ?? 'School life' }}</p>
                <h2 id="activities-title" class="home-section__title">{{ $h['events_section_title'] ?? 'School Activities' }} &amp; {{ $h['news_section_title'] ?? 'Latest News' }}</h2>
            </header>

            <div class="home-news-events__grid">
                <div>
                    <h3 class="home-news-events__heading">{{ $h['events_section_title'] ?? 'School Activities' }}</h3>
                    @forelse($activityEvents as $activity)
                        @php
                            $date = $activity->published_at ?? $activity->created_at;
                            $day = $date ? $date->format('d') : '—';
                            $month = $date ? strtoupper($date->format('M')) : '';
                        @endphp
                        <article class="event-item">
                            <div class="event-item__date">
                                <div class="event-item__date-day">{{ $day }}</div>
                                <div class="event-item__date-month">{{ $month }}</div>
                            </div>
                            <div>
                                <h4 class="event-item__title">
                                    <a href="{{ route('school-activities.show', $activity) }}" wire:navigate>{{ $activity->title }}</a>
                                </h4>
                                <p class="event-item__desc">{{ \Illuminate\Support\Str::limit(strip_tags($activity->excerpt ?? $activity->body ?? ''), 100) }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="home-section__empty">Activities will appear here once added in Admin.</p>
                    @endforelse
                    <a href="{{ route('school-activities') }}" class="section-link" wire:navigate>{{ $h['events_link_text'] ?? 'View all school activities' }} →</a>
                </div>

                <div>
                    <h3 class="home-news-events__heading">{{ $h['news_section_title'] ?? 'Latest News' }}</h3>
                    @forelse($activityNews as $activity)
                        <article class="news-item">
                            @if($activity->image_path)
                                <a href="{{ route('school-activities.show', $activity) }}" class="news-item__thumb" wire:navigate>
                                    <img src="{{ asset($activity->image_path) }}" alt="" loading="lazy">
                                </a>
                            @endif
                            <div>
                                <h4 class="news-item__title">
                                    <a href="{{ route('school-activities.show', $activity) }}" wire:navigate>{{ $activity->title }}</a>
                                </h4>
                                @if($activity->published_at)
                                    <time class="news-item__date">{{ $activity->published_at->format('M d, Y') }}</time>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="home-section__empty">News will appear here once added in Admin.</p>
                    @endforelse
                    <a href="{{ route('school-activities') }}" class="section-link" wire:navigate>{{ $h['news_link_text'] ?? 'Read all news & updates' }} →</a>
                </div>
            </div>
        </div>
    </section>
</div>
