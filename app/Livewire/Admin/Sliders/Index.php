<?php

namespace App\Livewire\Admin\Sliders;

use App\Models\HomeSlider;
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

    public ?string $image_path = null;

    public ?string $title = null;

    public ?string $caption = null;

    public ?string $button_text = null;

    public ?string $button_url = null;

    public bool $is_active = true;

    public ?int $sort_order = null;

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

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $s = HomeSlider::findOrFail($id);
        $this->editingId = $s->id;
        $this->image_path = $s->image_path;
        $this->title = $s->title ?? '';
        $this->caption = $s->caption ?? '';
        $this->button_text = $s->button_text ?? '';
        $this->button_url = $s->button_url ?? '';
        $this->is_active = $s->is_active;
        $this->sort_order = $s->sort_order;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'image_path' => [$this->editingId ? 'nullable' : 'required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['image_path'])) {
            AdminImageUploader::registerExisting($data['image_path'], 'sliders', 'sliders');
        }

        $payload = [
            'image_path' => $data['image_path'] ?? null,
            'title' => $data['title'] ?: null,
            'caption' => $data['caption'] ?: null,
            'button_text' => $data['button_text'] ?: null,
            'button_url' => $data['button_url'] ?: null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? null,
        ];

        if ($this->editingId) {
            $model = HomeSlider::findOrFail($this->editingId);
            if (empty($payload['image_path'])) {
                unset($payload['image_path']);
            }
            $model->update($payload);
            session()->flash('success', 'Slide updated successfully.');
        } else {
            HomeSlider::create($payload);
            session()->flash('success', 'Slide created successfully.');
        }

        $this->closeFormModal();
    }

    public function delete(int $id): void
    {
        HomeSlider::findOrFail($id)->delete();
        session()->flash('success', 'Slide deleted successfully.');
        $this->closeFormModal();
    }

    protected function resetForm(): void
    {
        $this->image_path = null;
        $this->title = null;
        $this->caption = null;
        $this->button_text = null;
        $this->button_url = null;
        $this->is_active = true;
        $this->sort_order = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = HomeSlider::query()->orderBy('sort_order')->orderByDesc('id');
        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)->orWhere('caption', 'like', $term);
            });
        }

        return view('livewire.admin.sliders.index', [
            'sliders' => $query->paginate(12),
        ]);
    }
}
