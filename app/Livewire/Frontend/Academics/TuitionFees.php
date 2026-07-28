<?php

namespace App\Livewire\Frontend\Academics;

use App\Livewire\Frontend\Concerns\LoadsPageHeader;
use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.frontend')]
class TuitionFees extends Component
{
    use LoadsPageHeader;

    public function render()
    {
        $settings = WebsiteSetting::first();
        $content = SiteContent::get($settings, 'academics.tuition_fees', []);

        return view('livewire.frontend.academics.page', [
            'header' => $this->pageHeader('academics_tuition', 'departments'),
            'content' => $content,
            'breadcrumb' => 'Tuition & Fees',
        ]);
    }
}
