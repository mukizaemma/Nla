<?php

namespace App\Livewire\Admin\Registrations;

use App\Mail\StudentRegistrationDecision;
use App\Models\StudentRegistration;
use App\Models\WebsiteSetting;
use App\Support\StudentRegistrationExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    #[Url(as: 'from', keep: true)]
    public ?string $dateFrom = null;

    #[Url(as: 'to', keep: true)]
    public ?string $dateTo = null;

    public ?int $selectedId = null;

    public string $editChannel = 'email';

    public string $responseMessage = '';

    public ?int $deletingId = null;

    public string $deletionReason = '';

    public function mount(): void
    {
        $open = request()->query('open');
        if ($open && is_numeric($open)) {
            $this->openRegistration((int) $open);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
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

    public function confirmDelete(int $id): void
    {
        StudentRegistration::findOrFail($id);
        $this->deletingId = $id;
        $this->deletionReason = '';
        $this->resetErrorBag('deletionReason');
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->deletionReason = '';
        $this->resetErrorBag('deletionReason');
    }

    public function deleteRegistration(): void
    {
        if (! $this->deletingId) {
            return;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $registration = StudentRegistration::findOrFail($this->deletingId);

        if ($user->isSuperAdmin()) {
            $registration->deleted_by = $user->id;
            $registration->deletion_reason = trim($this->deletionReason) !== ''
                ? trim($this->deletionReason)
                : 'Deleted by super admin';
            $registration->save();
            $registration->delete();
        } else {
            $this->validate([
                'deletionReason' => ['required', 'string', 'min:5', 'max:1000'],
            ], [
                'deletionReason.required' => 'Please explain why this registration is being removed (e.g. duplicate or not real).',
                'deletionReason.min' => 'Please provide a short reason (at least 5 characters).',
            ]);

            $registration->deleted_by = $user->id;
            $registration->deletion_reason = trim($this->deletionReason);
            $registration->save();
            $registration->delete();
        }

        if ($this->selectedId === $this->deletingId) {
            $this->closeRegistration();
        }

        $this->cancelDelete();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Registration removed',
            'text' => 'The registration has been deleted from the active list.',
        ]);
    }

    public function exportExcel()
    {
        $this->validateDateRange();

        return StudentRegistrationExporter::excel(
            StudentRegistrationExporter::collect($this->filteredQuery()),
            $this->dateFrom,
            $this->dateTo
        );
    }

    public function exportPdf()
    {
        $this->validateDateRange();

        return StudentRegistrationExporter::pdf(
            StudentRegistrationExporter::collect($this->filteredQuery()),
            $this->dateFrom,
            $this->dateTo
        );
    }

    protected function validateDateRange(): void
    {
        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
        ], [
            'dateTo.after_or_equal' => 'The end date must be on or after the start date.',
        ]);
    }

    /**
     * @return Builder<StudentRegistration>
     */
    protected function filteredQuery(): Builder
    {
        $query = StudentRegistration::query();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
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

        return $query;
    }

    protected function selectedRegistration(): ?StudentRegistration
    {
        if (! $this->selectedId) {
            return null;
        }

        return StudentRegistration::find($this->selectedId);
    }

    public function getIsSuperAdminProperty(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public function render()
    {
        return view('livewire.admin.registrations.index', [
            'registrations' => $this->filteredQuery()->orderByDesc('created_at')->paginate(15),
            'selected' => $this->selectedRegistration(),
            'deleting' => $this->deletingId
                ? StudentRegistration::find($this->deletingId)
                : null,
            'isSuperAdmin' => $this->isSuperAdmin,
        ]);
    }
}
