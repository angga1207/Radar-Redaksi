<?php

namespace App\Livewire;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.public')]
class NewsSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $term = Str::lower(trim($this->query));

        return view('livewire.news-search', [
            'articles' => Article::query()->published()->with(['author', 'category'])
                ->when(Str::length($term) >= 2, function ($query) use ($term): void {
                    $query->where(function ($search) use ($term): void {
                        if (DB::connection()->getDriverName() === 'pgsql') {
                            $search->whereFullText(['title', 'excerpt', 'body'], $term, ['language' => 'simple']);
                        }
                        $method = DB::connection()->getDriverName() === 'pgsql' ? 'orWhereRaw' : 'whereRaw';
                        $search->{$method}('LOWER(title) LIKE ?', ['%'.$term.'%'])
                            ->orWhereRaw('LOWER(excerpt) LIKE ?', ['%'.$term.'%'])
                            ->orWhereRaw('LOWER(body) LIKE ?', ['%'.$term.'%']);
                    });
                })
                ->when(Str::length($term) < 2, fn ($query) => $query->whereRaw('1 = 0'))
                ->latest('published_at')
                ->paginate(10),
        ]);
    }
}
