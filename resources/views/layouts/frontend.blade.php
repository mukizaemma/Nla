<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ (($websiteSettings ?? null)?->company_name ?? config('app.name')) . ' — ' . (($siteContent ?? [])['global']['meta_description'] ?? '') }}">
    <title>@yield('title', ($websiteSettings ?? null)?->company_name ?? config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('css/frontend.css') }}?v={{ file_exists(public_path('css/frontend.css')) ? filemtime(public_path('css/frontend.css')) : 1 }}" rel="stylesheet">
    <link href="{{ asset('css/pages.css') }}?v={{ file_exists(public_path('css/pages.css')) ? filemtime(public_path('css/pages.css')) : 1 }}" rel="stylesheet">
    @stack('styles')
    @livewireStyles
</head>
<body>
    <div id="site-loader" class="site-loader" aria-live="polite" aria-busy="true">
        <div class="loader-school" aria-hidden="true">
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="48" height="48">
                <path fill="#1A2E4E" d="M32 8l20 12v24L32 56 12 44V20z"/>
                <path fill="#F8C818" d="M32 18v20M26 28h12" stroke="#F8C818" stroke-width="2"/>
            </svg>
        </div>
        <span class="loader-school__text">Loading</span>
    </div>

    <div class="container">
        @php
            $websiteSettings = $websiteSettings ?? \App\Models\WebsiteSetting::first() ?? new \App\Models\WebsiteSetting;
            $siteContent = $siteContent ?? \App\Support\SiteContent::for($websiteSettings);
            $footerPartners = $footerPartners ?? collect();
            $schoolName = $websiteSettings->company_name ?? config('app.name');
            $phone = $websiteSettings->phone_reception ?? $websiteSettings->phone_urgency ?? '+250 786 900 580';
            $email = $websiteSettings->email ?? 'info@nla.ac.rw';
            $g = $siteContent['global'] ?? [];
            $diagnosticTestUrl = 'https://www.acediagnostictest.com/diagnostictest/?route=common/pages&page_identifier=diagnostictest';
            $aboutNavActive = request()->routeIs('about', 'academics.*', 'leadership.*');
        @endphp

        {{-- Top utility bar --}}
        <div class="topbar">
            <div class="topbar__inner">
                <div class="topbar__contact">
                    @if($phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z"/></svg>
                            {{ $phone }}
                        </a>
                    @endif
                    @if($email)
                        <a href="mailto:{{ $email }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            {{ $email }}
                        </a>
                    @endif
                </div>
                <div class="topbar__cta">
                    <span class="topbar__cta-text">{{ $g['topbar_announcement'] ?? 'Christ-centred ACE education — Now enrolling · Academic year 2026-2027' }}</span>
                </div>
            </div>
        </div>

        {{-- Main navigation --}}
        <nav class="navbar" x-data="{ mobileOpen: false, aboutOpen: false }" @keydown.escape.window="mobileOpen = false; aboutOpen = false">
            <div class="navbar__inner">
                <a href="{{ route('home') }}" class="navbar-brand" wire:navigate @click="mobileOpen = false">
                    @if($websiteSettings->logo_path ?? null)
                        <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $schoolName }}" class="navbar-brand__logo">
                    @endif
                    <span class="navbar-brand__text">
                        <span class="navbar-brand__title">New Life</span>
                        <span class="navbar-brand__subtitle">Leadership Academy</span>
                    </span>
                </a>

                <div class="navbar-links">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" wire:navigate>Home</a>

                    <div class="nav-dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" :class="{ 'is-open': open }">
                        <button type="button" class="nav-dropdown__toggle {{ $aboutNavActive ? 'active' : '' }}" @click="open = !open" :aria-expanded="open">
                            About
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <div class="nav-dropdown__menu" x-show="open" x-cloak x-transition>
                            <a href="{{ route('about') }}" wire:navigate>Our School</a>
                            <a href="{{ route('academics.about-ace') }}" wire:navigate>About ACE</a>
                            <a href="{{ route('departments.index') }}" wire:navigate>Academics</a>
                            <a href="{{ route('leadership.index') }}" wire:navigate>Staff</a>
                            <a href="{{ route('school-activities') }}" wire:navigate>Updates</a>
                        </div>
                    </div>

                    <a href="{{ route('departments.index') }}" class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" wire:navigate>Programs</a>
                    <a href="{{ route('school-activities') }}" class="nav-link {{ request()->routeIs('school-activities*') ? 'active' : '' }}" wire:navigate>School Activities</a>
                    <a href="{{ route('facilities') }}" class="nav-link {{ request()->routeIs('facilities') ? 'active' : '' }}" wire:navigate>Facilities</a>
                    <a href="{{ route('gallery.index') }}" class="nav-link {{ request()->routeIs('gallery.*') ? 'active' : '' }}" wire:navigate>Gallery</a>
                </div>

                <div class="navbar-actions">
                    <a href="{{ route('appointment') }}" class="btn btn--dark btn--sm" wire:navigate>Register</a>
                    <a href="{{ $diagnosticTestUrl }}" class="btn btn--outline btn--sm navbar-actions__diagnostic" target="_blank" rel="noopener noreferrer">Take Diagnostic Test</a>
                    <button type="button" class="navbar-toggle" :aria-expanded="mobileOpen" aria-controls="navbar-mobile" aria-label="Open menu" @click="mobileOpen = !mobileOpen">
                        <span class="navbar-toggle__bar"></span>
                        <span class="navbar-toggle__bar"></span>
                        <span class="navbar-toggle__bar"></span>
                    </button>
                </div>
            </div>

            <div id="navbar-mobile" class="navbar-mobile-panel" :class="{ 'is-open': mobileOpen }" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false">
                <a href="{{ route('home') }}" wire:navigate @click="mobileOpen = false">Home</a>
                <button type="button" @click="aboutOpen = !aboutOpen">About ▾</button>
                <div class="navbar-mobile-panel__sub" x-show="aboutOpen" x-cloak>
                    <a href="{{ route('about') }}" wire:navigate @click="mobileOpen = false">Our School</a>
                    <a href="{{ route('academics.about-ace') }}" wire:navigate @click="mobileOpen = false">About ACE</a>
                    <a href="{{ route('departments.index') }}" wire:navigate @click="mobileOpen = false">Academics</a>
                    <a href="{{ route('leadership.index') }}" wire:navigate @click="mobileOpen = false">Staff</a>
                    <a href="{{ route('school-activities') }}" wire:navigate @click="mobileOpen = false">Updates</a>
                </div>
                <a href="{{ route('departments.index') }}" wire:navigate @click="mobileOpen = false">Programs</a>
                <a href="{{ route('school-activities') }}" wire:navigate @click="mobileOpen = false">School Activities</a>
                <a href="{{ route('facilities') }}" wire:navigate @click="mobileOpen = false">Facilities</a>
                <a href="{{ route('gallery.index') }}" wire:navigate @click="mobileOpen = false">Gallery</a>
                <a href="{{ route('appointment') }}" class="btn btn--dark" wire:navigate @click="mobileOpen = false">Register</a>
                <a href="{{ $diagnosticTestUrl }}" class="btn btn--outline" target="_blank" rel="noopener noreferrer" @click="mobileOpen = false">Take Diagnostic Test</a>
            </div>
        </nav>

        <main class="main-content">
            {{ $slot ?? '' }}
        </main>

        {{-- Footer --}}
        <footer class="footer">
            @php
                $footerTagline = $g['topbar_tagline'] ?? 'Pursuing Education to the Glory of God';
                $footerSchool = $websiteSettings->company_name ?? config('app.name');
                $hasPartners = isset($footerPartners) && $footerPartners->isNotEmpty();
            @endphp

            <div class="footer__main">
                <div class="footer__col footer__col--brand">
                    <a href="{{ route('home') }}" wire:navigate class="footer__logo">
                        @if($websiteSettings->logo_path ?? null)
                            <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $footerSchool }}" class="footer__logo-img">
                        @endif
                    </a>
                    <p class="footer__brand-name">{{ $footerSchool }}</p>
                    <p class="footer__vision">{{ $footerTagline }}</p>
                    <x-footer-social :settings="$websiteSettings" class="footer__social--brand" />
                </div>

                <div class="footer__col footer__col--menu">
                    <h3 class="footer__heading">{{ $g['footer_menu_heading'] ?? 'Explore' }}</h3>
                    <nav class="footer__nav footer__nav--split" aria-label="Footer">
                        <div class="footer__nav-col">
                            <a href="{{ route('home') }}" wire:navigate>Home</a>
                            <a href="{{ route('about') }}" wire:navigate>Our School</a>
                            <a href="{{ route('academics.about-ace') }}" wire:navigate>About ACE</a>
                            <a href="{{ route('departments.index') }}" wire:navigate>Programs</a>
                            <a href="{{ route('school-activities') }}" wire:navigate>School Activities</a>
                        </div>
                        <div class="footer__nav-col">
                            <a href="{{ route('facilities') }}" wire:navigate>Facilities</a>
                            <a href="{{ route('gallery.index') }}" wire:navigate>Gallery</a>
                            <a href="{{ route('leadership.index') }}" wire:navigate>Staff</a>
                            <a href="{{ route('contact') }}" wire:navigate>Contacts</a>
                            <a href="{{ route('appointment') }}" wire:navigate>Register</a>
                        </div>
                    </nav>
                </div>

                <div class="footer__col footer__col--contact">
                    <h3 class="footer__heading">Get in touch</h3>
                    <div class="footer__contacts">
                        @if($websiteSettings->address ?? null)
                            <div class="footer__contact-item">
                                <svg class="footer__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>{{ $websiteSettings->address }}</span>
                            </div>
                        @endif
                        @if($phone)
                            <div class="footer__contact-item">
                                <svg class="footer__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z"/></svg>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
                            </div>
                        @endif
                        @if($email)
                            <div class="footer__contact-item">
                                <svg class="footer__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="footer__cta">
                        <a href="{{ route('appointment') }}" class="footer__register-btn" wire:navigate>Register</a>
                        <a href="{{ $diagnosticTestUrl }}" class="footer__diagnostic-btn" target="_blank" rel="noopener noreferrer">Take Diagnostic Test</a>
                    </div>
                </div>
            </div>

            @if($hasPartners)
                <div class="footer__partners-band">
                    <div class="footer__partners-inner">
                        <x-partners-panel :partners="$footerPartners->take(4)" variant="footer" heading="Partners & Accreditation" />
                    </div>
                </div>
            @endif

            <div class="footer__bottom">
                <div class="footer__copyright">
                    Copyright © {{ date('Y') }} {{ $footerSchool }}. All rights reserved.
                </div>
                <div class="footer__developed">
                    {!! $g['developer_credit'] ?? 'Developed by <a href="https://iremetech.com" target="_blank" rel="noopener noreferrer">Ireme Technologies</a>' !!}
                </div>
            </div>
        </footer>

        @if($websiteSettings->phone_whatsapp ?? null)
            @php $waNumber = preg_replace('/[^0-9]/', '', $websiteSettings->phone_whatsapp); @endphp
            @if($waNumber)
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat on WhatsApp">
                    <svg viewBox="0 0 32 32" fill="currentColor" width="28" height="28" aria-hidden="true">
                        <path d="M16 0C7.164 0 0 7.164 0 16c0 2.825.736 5.48 2.024 7.784L.056 31.68l8.064-2.112A15.92 15.92 0 0016 32c8.836 0 16-7.164 16-16S24.836 0 16 0zm0 29.333c-2.616 0-5.084-.696-7.22-1.912l-.508-.3-5.264 1.38 1.408-5.14-.332-.528A13.22 13.22 0 012.667 16c0-7.364 5.969-13.333 13.333-13.333S29.333 8.636 29.333 16 23.364 29.333 16 29.333zm7.316-9.964c-.392-.196-2.316-1.144-2.676-1.272-.36-.128-.624-.196-.888.196-.264.392-1.024 1.272-1.256 1.532-.232.26-.464.292-.856.096-.392-.196-1.656-.612-3.156-1.948-1.168-1.04-1.952-2.324-2.18-2.716-.228-.392-.024-.604.172-.796.176-.176.392-.46.588-.688.196-.228.26-.392.392-.656.132-.264.066-.492-.032-.688-.1-.196-.888-2.14-1.216-2.928-.324-.776-.656-.672-.888-.684l-.756-.016c-.264 0-.688.096-1.048.476-.36.38-1.376 1.344-1.376 3.276 0 1.932 1.408 3.036 1.604 3.244.196.208 2.776 4.244 6.724 5.828.936.376 1.668.6 2.236.768.936.272 1.788.232 2.46.14.752-.104 2.316-.948 2.64-1.86.324-.912.324-1.692.228-1.86-.096-.168-.36-.264-.752-.46z"/>
                    </svg>
                </a>
            @endif
        @endif
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loader = document.getElementById('site-loader');
            if (loader) loader.classList.add('hidden');
            if (window.Livewire) {
                Livewire.on('swal', (data = {}) => {
                    Swal.fire({
                        toast: true, position: data.position || 'top-end',
                        timer: data.timer ?? 2800, timerProgressBar: true,
                        showConfirmButton: data.showConfirmButton ?? false,
                        icon: data.icon || 'success', title: data.title || 'Done', text: data.text || ''
                    });
                });
            }
        });
        document.addEventListener('livewire:navigated', function() {
            var loader = document.getElementById('site-loader');
            if (loader) loader.classList.add('hidden');
        });
    </script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
