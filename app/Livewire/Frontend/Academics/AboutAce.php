<?php

namespace App\Livewire\Frontend\Academics;

use App\Livewire\Frontend\Concerns\LoadsPageHeader;
use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.frontend')]
class AboutAce extends Component
{
    use LoadsPageHeader;

    public function render()
    {
        $settings = WebsiteSetting::first();
        $content = SiteContent::get($settings, 'academics.about_ace', []);

        return view('livewire.frontend.academics.page', [
            'header' => $this->pageHeader('academics_about_ace', 'departments'),
            'content' => $content,
            'breadcrumb' => 'About ACE',
        ]);
    }
}
