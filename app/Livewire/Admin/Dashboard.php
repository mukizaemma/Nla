<?php

namespace App\Livewire\Admin;

use App\Models\ClinicalDepartment;
use App\Models\ContactMessage;
use App\Models\CustomerFeedback;
use App\Models\StudentRegistration;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $programCount = ClinicalDepartment::count();
        $contactMessageCount = ContactMessage::count();
        $feedbackCount = CustomerFeedback::count();
        $registrationCount = StudentRegistration::count();
        $recentRegistrations = StudentRegistration::query()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('livewire.admin.dashboard', [
            'programCount' => $programCount,
            'contactMessageCount' => $contactMessageCount,
            'feedbackCount' => $feedbackCount,
            'registrationCount' => $registrationCount,
            'recentRegistrations' => $recentRegistrations,
        ]);
    }
}
