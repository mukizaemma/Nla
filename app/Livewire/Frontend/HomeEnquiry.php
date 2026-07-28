<?php

namespace App\Livewire\Frontend;

use App\Models\ContactMessage;
use App\Models\WebsiteSetting;
use Livewire\Component;

class HomeEnquiry extends Component
{
    public string $parent_name = '';
    public string $email = '';
    public string $phone = '';
    public string $grade = '';
    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'parent_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'grade' => ['required', 'string', 'max:50'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $parts = preg_split('/\s+/', trim($this->parent_name), 2);
        $firstName = $parts[0] ?? 'Parent';
        $lastName = $parts[1] ?? '';

        ContactMessage::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'subject' => 'Admission callback request — '.$this->grade,
            'message' => 'Callback request from homepage admission form. Grade/level: '.$this->grade.'.',
            'submission_channel' => 'email',
        ]);

        $this->reset(['parent_name', 'email', 'phone', 'grade']);
        $this->submitted = true;
    }

    public function render()
    {
        $h = \App\Support\SiteContent::get(WebsiteSetting::first(), 'home', []);

        return view('livewire.frontend.home-enquiry', [
            'h' => $h,
        ]);
    }
}
