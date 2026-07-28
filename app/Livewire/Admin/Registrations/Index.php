<?php

namespace App\Livewire\Admin\Registrations;

use App\Mail\StudentRegistrationDecision;
use App\Models\StudentRegistration;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $selectedId = null;

    public string $editChannel = 'email';

    public string $responseMessage = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openRegistration(int $id): void
    {
        $registration = StudentRegistration::findOrFail($id);
        $this->selectedId = $registration->id;
        $this->editChannel = in_array($registration->submission_channel, ['email', 'whatsapp'], true)
            ? $registration->submission_channel
            : 'email';
        $this->responseMessage = $registration->admin_response_message ?? '';
        $this->resetErrorBag();
    }

    public function closeRegistration(): void
    {
        $this->selectedId = null;
        $this->responseMessage = '';
        $this->resetErrorBag();
    }

    public function updateChannel(): void
    {
        $this->validate([
            'editChannel' => ['required', Rule::in(['email', 'whatsapp'])],
        ]);

        $registration = $this->selectedRegistration();
        if (! $registration) {
            return;
        }

        if ($this->editChannel === 'email' && ! $registration->primaryContactEmail()) {
            $this->addError('editChannel', 'This application has no contact email. Keep WhatsApp or ask the parent to update details.');

            return;
        }

        if ($this->editChannel === 'whatsapp' && ! $registration->whatsappDigits()) {
            $this->addError('editChannel', 'This application has no valid WhatsApp phone number.');

            return;
        }

        $registration->update(['submission_channel' => $this->editChannel]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Channel updated',
            'text' => 'Preferred follow-up is now ' . $this->editChannel . '.',
        ]);
    }

    public function confirmApplication(): void
    {
        $this->respond(StudentRegistration::STATUS_CONFIRMED);
    }

    public function rejectApplication(): void
    {
        $this->respond(StudentRegistration::STATUS_REJECTED);
    }

    protected function respond(string $status): void
    {
        $this->validate([
            'responseMessage' => ['required', 'string', 'min:5', 'max:2000'],
            'editChannel' => ['required', Rule::in(['email', 'whatsapp'])],
        ], [
            'responseMessage.required' => 'Please write a message for the parent.',
            'responseMessage.min' => 'Please write a short message for the parent (at least 5 characters).',
        ]);

        $registration = $this->selectedRegistration();
        if (! $registration) {
            return;
        }

        if (! $registration->isPending()) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Already decided',
                'text' => 'This application was already ' . $registration->status . '.',
            ]);

            return;
        }

        // Persist any channel change before sending.
        $registration->submission_channel = $this->editChannel;
        $registration->status = $status;
        $registration->admin_response_message = trim($this->responseMessage);
        $registration->responded_at = now();
        $registration->responded_by = Auth::id();
        $registration->save();

        $schoolName = WebsiteSetting::first()?->company_name ?? config('app.name');

        if ($registration->prefersEmail()) {
            $email = $registration->primaryContactEmail();
            if (! $email) {
                $this->addError('editChannel', 'No parent email on file. Switch to WhatsApp or add an email first.');
                $registration->update([
                    'status' => StudentRegistration::STATUS_PENDING,
                    'admin_response_message' => null,
                    'responded_at' => null,
                    'responded_by' => null,
                ]);

                return;
            }

            try {
                Mail::to($email)->send(new StudentRegistrationDecision($registration, $schoolName));
            } catch (\Throwable $e) {
                Log::warning('Failed to send registration decision email', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Email failed',
                    'text' => 'Decision was saved, but the email could not be sent. Check Resend configuration.',
                    'timer' => 5000,
                ]);
                $this->closeRegistration();

                return;
            }

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => $status === StudentRegistration::STATUS_CONFIRMED ? 'Confirmed' : 'Rejected',
                'text' => 'The parent has been emailed automatically.',
            ]);
            $this->closeRegistration();

            return;
        }

        $waUrl = $registration->decisionWhatsAppUrl($schoolName);
        if (! $waUrl) {
            $this->addError('editChannel', 'No valid WhatsApp number on file for this parent.');

            return;
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $status === StudentRegistration::STATUS_CONFIRMED ? 'Confirmed' : 'Rejected',
            'text' => 'Opening WhatsApp so you can send the decision message.',
            'timer' => 2200,
        ]);
        $this->dispatch('open-url', url: $waUrl);
        $this->closeRegistration();
    }

    protected function selectedRegistration(): ?StudentRegistration
    {
        if (! $this->selectedId) {
            return null;
        }

        return StudentRegistration::find($this->selectedId);
    }

    public function render()
    {
        $query = StudentRegistration::query()->orderByDesc('created_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('student_first_name', 'like', $term)
                    ->orWhere('student_last_name', 'like', $term)
                    ->orWhere('academic_level', 'like', $term)
                    ->orWhere('father_full_name', 'like', $term)
                    ->orWhere('father_email', 'like', $term)
                    ->orWhere('father_phone', 'like', $term)
                    ->orWhere('mother_full_name', 'like', $term)
                    ->orWhere('mother_email', 'like', $term)
                    ->orWhere('mother_phone', 'like', $term)
                    ->orWhere('guardian_full_name', 'like', $term)
                    ->orWhere('guardian_email', 'like', $term)
                    ->orWhere('guardian_phone', 'like', $term)
                    ->orWhere('submission_channel', 'like', $term);
            });
        }

        return view('livewire.admin.registrations.index', [
            'registrations' => $query->paginate(15),
            'selected' => $this->selectedRegistration(),
        ]);
    }
}
