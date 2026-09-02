<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Media;
use App\Services\ImageOptimizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class MediaLibrary extends Component
{
    use WithFileUploads;

    public $file;

    public string $altText = '';

    public string $caption = '';

    public string $credit = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        abort_unless(auth()->user()->hasPermission('media.manage') || $media->uploader_id === auth()->id(), 403);
        $this->editingId = $media->id;
        $this->altText = $media->alt_text;
        $this->caption = $media->caption ?? '';
        $this->credit = $media->credit ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function saveMedia(ImageOptimizer $imageOptimizer): void
    {
        abort_unless(auth()->user()->hasPermission('media.manage') || auth()->user()->hasPermission('media.upload'), 403);
        $data = $this->validate(['file' => [$this->editingId ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'altText' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:500'], 'credit' => ['nullable', 'string', 'max:255']]);
        if ($this->editingId) {
            $media = Media::query()->findOrFail($this->editingId);
            abort_unless(auth()->user()->hasPermission('media.manage') || $media->uploader_id === auth()->id(), 403);
            $media->update(['alt_text' => $data['altText'] ?: $this->altTextFromFilename($media->filename), 'caption' => $data['caption'] ?: null, 'credit' => $data['credit'] ?: null]);
            AuditLog::record('media.updated', $media);
            $this->closeModal();
            session()->flash('success', 'Metadata media berhasil diperbarui.');

            return;
        }
        $filename = $data['file']->getClientOriginalName();
        $optimized = $imageOptimizer->store($data['file'], 'media/'.now()->format('Y/m'), createThumbnail: true);
        $media = Media::query()->create(['uploader_id' => auth()->id(), 'disk' => 'public', 'path' => $optimized['path'], 'thumbnail_path' => $optimized['thumbnail_path'], 'filename' => $filename, 'mime_type' => $optimized['mime_type'], 'size' => $optimized['size'], 'width' => $optimized['width'], 'height' => $optimized['height'], 'alt_text' => $data['altText'] ?: $this->altTextFromFilename($filename), 'caption' => $data['caption'] ?: null, 'credit' => $data['credit'] ?: null]);
        AuditLog::record('media.uploaded', $media);
        $this->closeModal();
        session()->flash('success', 'Gambar berhasil ditambahkan ke pustaka media.');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('media.manage'), 403);
        $media = Media::query()->findOrFail($id);
        Storage::disk($media->disk)->delete($media->path);
        if ($media->thumbnail_path) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }
        AuditLog::record('media.deleted', $media);
        $media->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.media-library', ['mediaItems' => Media::query()->with('uploader')->latest()->paginate(18)]);
    }

    private function resetForm(): void
    {
        $this->reset(['file', 'altText', 'caption', 'credit', 'editingId']);
        $this->resetValidation();
    }

    private function altTextFromFilename(string $filename): string
    {
        return Str::of(pathinfo($filename, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->headline()->toString();
    }
}
