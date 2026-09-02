<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PageForm extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $slug = '';

    public string $body = '';

    public string $status = 'draft';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public function mount(?Page $page = null): void
    {
        abort_unless(auth()->user()->hasPermission('pages.manage'), 403);
        if ($page?->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->slug = $page->slug;
            $this->body = $page->body;
            $this->status = $page->status;
            $this->seoTitle = $page->seo_title ?? '';
            $this->seoDescription = $page->seo_description ?? '';
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('pages.manage'), 403);
        $this->slug = $this->page?->slug ?? $this->uniqueSlug($this->title);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('pages')->ignore($this->page?->id)],
            'body' => ['required', 'string', 'min:20'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:320'],
        ]);
        $wasPublished = $this->page?->status === 'published';
        $page = Page::query()->updateOrCreate(['id' => $this->page?->id], [
            'title' => $data['title'], 'slug' => $data['slug'],
            'body' => HtmlSanitizer::clean($data['body']),
            'status' => $data['status'], 'seo_title' => $data['seoTitle'] ?: null,
            'seo_description' => $data['seoDescription'] ?: null,
            'published_at' => $data['status'] === 'published' ? ($wasPublished ? $this->page?->published_at : now()) : null,
        ]);
        AuditLog::record($this->page ? 'page.updated' : 'page.created', $page);
        session()->flash('success', 'Halaman berhasil disimpan.');

        $this->redirectRoute('admin.pages.index', navigate: true);
    }

    private function uniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: 'halaman';
        $slug = $baseSlug;
        $suffix = 2;

        while (Page::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    public function render(): View
    {
        return view('livewire.admin.page-form');
    }
}
