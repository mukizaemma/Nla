<?php

namespace App\Livewire\Admin;

use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PageContent extends Component
{
    public string $activeTab = 'global';

    /** @var array<string, mixed> */
    public array $sections = [];

    public function mount(): void
    {
        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $this->sections = SiteContent::for($settings);

        if (empty($this->sections['about']['core_value_cards']) && ! empty($settings->about_value_cards)) {
            $cards = is_array($settings->about_value_cards)
                ? $settings->about_value_cards
                : (json_decode((string) $settings->about_value_cards, true) ?: []);
            $this->sections['about']['core_value_cards'] = is_array($cards) ? array_values($cards) : [];
        }

    }

    public function setTab(string $tab): void
    {
        $allowed = ['global', 'home', 'about', 'facilities', 'contact', 'departments', 'activities', 'gallery', 'careers', 'leadership', 'feedback', 'registration'];
        if (in_array($tab, $allowed, true)) {
            $this->activeTab = $tab;
        }
    }

    public function addCurriculumPillar(): void
    {
        $this->sections['home']['curriculum_pillars'][] = ['title' => '', 'description' => ''];
    }

    public function removeCurriculumPillar(int $index): void
    {
        $pillars = $this->sections['home']['curriculum_pillars'] ?? [];
        array_splice($pillars, $index, 1);
        $this->sections['home']['curriculum_pillars'] = array_values($pillars);
    }

    public function addExploreCard(): void
    {
        $this->sections['home']['explore_cards'][] = [
            'key' => 'custom',
            'title' => '',
            'description' => '',
            'url' => '/',
        ];
    }

    public function removeExploreCard(int $index): void
    {
        $cards = $this->sections['home']['explore_cards'] ?? [];
        array_splice($cards, $index, 1);
        $this->sections['home']['explore_cards'] = array_values($cards);
    }

    public function addCoreValueCard(): void
    {
        $this->sections['about']['core_value_cards'][] = ['name' => '', 'description' => ''];
    }

    public function removeCoreValueCard(int $index): void
    {
        $cards = $this->sections['about']['core_value_cards'] ?? [];
        array_splice($cards, $index, 1);
        $this->sections['about']['core_value_cards'] = array_values($cards);
    }

    public function addAcademicLevel(): void
    {
        $this->sections['registration']['academic_levels'][] = '';
    }

    public function removeAcademicLevel(int $index): void
    {
        $levels = $this->sections['registration']['academic_levels'] ?? [];
        array_splice($levels, $index, 1);
        $this->sections['registration']['academic_levels'] = array_values($levels);
    }

    public function save(): void
    {
        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);

        $coreCards = array_values(array_filter(
            $this->sections['about']['core_value_cards'] ?? [],
            fn ($c) => ! empty($c['name']) || ! empty($c['description'])
        ));
        $this->sections['about']['core_value_cards'] = $coreCards;

        $settings->update([
            'page_sections' => $this->sections,
            'about_value_cards' => $coreCards,
        ]);

        session()->flash('message', 'Page content updated successfully.');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Saved',
            'text' => 'Website page content has been updated.',
        ]);
    }

    public function resetToDefaults(): void
    {
        $this->sections = SiteContent::defaults();
    }

    public function render()
    {
        return view('livewire.admin.page-content');
    }
}
