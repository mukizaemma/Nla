<?php

namespace App\Livewire\Frontend;

use App\Models\ClinicalDepartment;
use App\Models\Facility;
use App\Models\HomeSlider;
use App\Models\MediaGalleryItem;
use App\Models\Partner;
use App\Models\SchoolActivity;
use App\Models\WebsiteSetting;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Home extends Component
{
    #[Layout('layouts.frontend')]
    public function render()
    {
        $settings = WebsiteSetting::first();
        $sliders = HomeSlider::where('is_active', true)->orderBy('sort_order')->get();
        $departments = ClinicalDepartment::where('is_active', true)->orderBy('sort_order')->get();
        $partners = Partner::where('is_active', true)->orderBy('sort_order')->get();
        $facilities = Facility::where('is_active', true)->orderBy('sort_order')->limit(6)->get();

        $galleryImages = MediaGalleryItem::query()
            ->where('is_active', true)
            ->where('type', 'image')
            ->whereNotNull('image_path')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $upcomingActivities = SchoolActivity::query()
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $programs = ClinicalDepartment::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(2)
            ->get();

        $whyChooseCards = [];
        if ($settings?->about_value_cards) {
            $whyChooseCards = is_string($settings->about_value_cards)
                ? json_decode($settings->about_value_cards, true)
                : $settings->about_value_cards;
            $whyChooseCards = is_array($whyChooseCards)
                ? array_values(array_filter($whyChooseCards, fn ($c) => ! empty($c['name'] ?? null)))
                : [];
        }
        if (empty($whyChooseCards)) {
            $fromContent = \App\Support\SiteContent::get($settings, 'about.core_value_cards', []);
            $whyChooseCards = is_array($fromContent)
                ? array_values(array_filter($fromContent, fn ($c) => ! empty($c['name'] ?? null)))
                : [];
        }

        $facilityImage = Facility::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->orderBy('sort_order')
            ->value('image_path');

        $activityImage = SchoolActivity::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->orderByDesc('published_at')
            ->value('image_path');

        return view('livewire.frontend.home', [
            'settings' => $settings,
            'sliders' => $sliders,
            'departments' => $departments,
            'programs' => $programs,
            'partners' => $partners,
            'facilities' => $facilities,
            'galleryImages' => $galleryImages,
            'upcomingActivities' => $upcomingActivities,
            'whyChooseCards' => $whyChooseCards,
            'facilityImage' => $facilityImage,
            'activityImage' => $activityImage,
        ]);
    }
}
