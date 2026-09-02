<?php

namespace App\Livewire\Admin;

use App\Models\Advertisement;
use App\Models\AuditLog;
use App\Services\ImageOptimizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AdvertisementManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public string $placement = 'homepage_top';

    public string $title = '';

    public string $imageUrl = '';

    public ?TemporaryUploadedFile $imageUpload = null;

    public string $destinationUrl = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    public function edit(int $id): void
    {
        $advertisement = Advertisement::query()->findOrFail($id);
        $this->editingId = $advertisement->id;
        $this->placement = $advertisement->placement;
        $this->title = $advertisement->title;
        $this->imageUrl = $advertisement->image_url;
        $this->destinationUrl = $advertisement->destination_url;
        $this->startsAt = $advertisement->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $advertisement->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->isActive = $advertisement->is_active;
        $this->dispatch('tom-select:set', property: 'placement', value: $this->placement);
    }

    public function save(ImageOptimizer $imageOptimizer): void
    {
        abort_unless(auth()->user()->hasPermission('advertisements.manage'), 403);
        $data = $this->validate([
            'placement' => ['required', Rule::in(['homepage_top', 'homepage_sidebar'])], 'title' => ['required', 'string', 'max:150'],
            'imageUpload' => [Rule::requiredIf(! $this->editingId), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'destinationUrl' => ['required', 'url:http,https', 'max:2048'],
            'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'isActive' => ['boolean'],
        ]);
        $oldImageUrl = $this->imageUrl;
        $imagePath = $this->imageUpload ? $imageOptimizer->store($this->imageUpload, 'advertisements')['path'] : null;
        $advertisement = Advertisement::query()->updateOrCreate(['id' => $this->editingId], [
            'placement' => $data['placement'], 'title' => $data['title'], 'image_url' => $imagePath ? Storage::disk('public')->url($imagePath) : $this->imageUrl, 'destination_url' => $data['destinationUrl'],
            'starts_at' => $data['startsAt'] ?: null, 'ends_at' => $data['endsAt'] ?: null, 'is_active' => $data['isActive'],
        ]);
        AuditLog::record($this->editingId ? 'advertisement.updated' : 'advertisement.created', $advertisement);
        if ($imagePath) {
            $imageOptimizer->deletePublicUrl($oldImageUrl, 'advertisements');
        }
        Cache::forget('public.advertisements');
        $this->resetForm();
        session()->flash('success', 'Iklan berhasil disimpan.');
    }

    public function delete(int $id, ImageOptimizer $imageOptimizer): void
    {
        abort_unless(auth()->user()->hasPermission('advertisements.manage'), 403);
        $advertisement = Advertisement::query()->findOrFail($id);
        AuditLog::record('advertisement.deleted', $advertisement);
        $imageOptimizer->deletePublicUrl($advertisement->image_url, 'advertisements');
        $advertisement->delete();
        Cache::forget('public.advertisements');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'imageUrl', 'imageUpload', 'destinationUrl', 'startsAt', 'endsAt']);
        $this->placement = 'homepage_top';
        $this->isActive = true;
        $this->dispatch('tom-select:set', property: 'placement', value: 'homepage_top');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('advertisements.manage'), 403);

        return view('livewire.admin.advertisement-manager', ['advertisements' => Advertisement::query()->latest()->paginate(12)]);
    }
}
