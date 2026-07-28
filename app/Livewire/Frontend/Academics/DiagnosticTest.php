<?php

namespace App\Livewire\Frontend\Academics;

use App\Livewire\Frontend\Concerns\LoadsPageHeader;
use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.frontend')]
class DiagnosticTest extends Component
{
    use LoadsPageHeader;

    public function render()
    {
        $settings = WebsiteSetting::first();
        $content = SiteContent::get($settings, 'academics.diagnostic_test', []);

        return view('livewire.frontend.academics.page', [
            'header' => $this->pageHeader('academics_diagnostic', 'departments'),
            'content' => $content,
            'breadcrumb' => 'ACE Diagnostic Test',
        ]);
    }
}
