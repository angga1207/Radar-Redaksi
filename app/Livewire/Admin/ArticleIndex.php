<?php

namespace App\Livewire\Admin;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ArticleIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showTrashed = false;

    /** @var array<int, string|int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function delete(int $articleId): void
    {
        $article = Article::query()->findOrFail($articleId);
        $this->authorize('delete', $article);
        $article->delete();
        Cache::forget('public.home-content.v2');
        session()->flash('success', 'Berita dipindahkan ke sampah.');
    }

    public function restore(int $articleId): void
    {
        abort_unless(auth()->user()->role === 'super_admin', 403);
        Article::onlyTrashed()->findOrFail($articleId)->restore();
        Cache::forget('public.home-content.v2');
        session()->flash('success', 'Berita berhasil dipulihkan.');
    }

    public function forceDelete(int $articleId): void
    {
        abort_unless(auth()->user()->role === 'super_admin', 403);
        Article::onlyTrashed()->findOrFail($articleId)->forceDelete();
        Cache::forget('public.home-content.v2');
        session()->flash('success', 'Berita dihapus permanen.');
    }

    public function bulkDelete(): void
    {
        $articles = Article::query()->whereKey($this->selected)->get();
        foreach ($articles as $article) {
            $this->authorize('delete', $article);
            $article->delete();
        }
        $this->selected = [];
        Cache::forget('public.home-content.v2');
        session()->flash('success', 'Berita terpilih dipindahkan ke sampah.');
    }

    public function render(): View
    {
        $search = Str::lower(trim($this->search));

        return view('livewire.admin.article-index', [
            'articles' => Article::query()->when($this->showTrashed, fn ($query) => $query->onlyTrashed())->with(['author', 'category'])
                ->when(! auth()->user()->isAdmin(), fn ($query) => $query->whereBelongsTo(auth()->user(), 'author'))
                ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->whereRaw('LOWER(title) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', ['%'.$search.'%'])))
                ->when($this->status, fn ($query) => $query->where('status', $this->status))
                ->latest()->paginate(12),
            'statuses' => ArticleStatus::cases(),
        ]);
    }
}
