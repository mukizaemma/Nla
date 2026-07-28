<?php

namespace App\Livewire\Admin\LeadershipTeam;

use App\Models\LeadershipTeamMember;
use App\Support\AdminImageUploader;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public bool $showFormModal = false;

    public string $full_name = '';
    public string $position = '';
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $biography = null;
    public ?string $profile_image = null;
    public bool $is_active = true;
    public ?int $sort_order = null;

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'biography' => ['nullable', 'string'],
            'profile_image' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $member = LeadershipTeamMember::findOrFail($id);
        $this->editingId = $member->id;
        $this->full_name = $member->full_name;
        $this->position = $member->position;
        $this->phone = $member->phone ?? '';
        $this->email = $member->email ?? '';
        $this->biography = $member->biography ?? '';
        $this->profile_image = $member->profile_image;
        $this->is_active = $member->is_active;
        $this->sort_order = $member->sort_order;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    public function save(): void
    {
        $data = $this->validate();

        if (! empty($data['profile_image'])) {
            AdminImageUploader::registerExisting($data['profile_image'], 'leadership', 'leadership');
        }

        $payload = [
            'full_name' => $data['full_name'],
            'position' => $data['position'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'biography' => $data['biography'] ?: null,
            'profile_image' => $data['profile_image'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? null,
        ];

        if ($this->editingId) {
            $model = LeadershipTeamMember::findOrFail($this->editingId);
            if (empty($payload['profile_image'])) {
                unset($payload['profile_image']);
            }
            $model->update($payload);
            session()->flash('success', 'Leadership team member updated successfully.');
        } else {
            LeadershipTeamMember::create($payload);
            session()->flash('success', 'Leadership team member added successfully.');
        }

        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = false;
    }

    public function delete(int $id): void
    {
        LeadershipTeamMember::findOrFail($id)->delete();
        session()->flash('success', 'Leadership team member removed successfully.');
        $this->showFormModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    protected function resetForm(): void
    {
        $this->full_name = '';
        $this->position = '';
        $this->phone = null;
        $this->email = null;
        $this->biography = null;
        $this->profile_image = null;
        $this->is_active = true;
        $this->sort_order = null;
        $this->resetValidation();
    }

    public function getMembersProperty()
    {
        return LeadershipTeamMember::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                    ->orWhere('position', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('sort_order')
            ->orderBy('full_name')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.leadership-team.index', [
            'members' => $this->members,
        ]);
    }
}
