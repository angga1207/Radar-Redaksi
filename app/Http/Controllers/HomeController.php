<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $contentIds = Cache::flexible('public.home-content.v2', [60, 300], function (): array {
            $base = Article::query()->published();
            $headline = (clone $base)->where('is_headline', true)->latest('published_at')->first()
                ?? (clone $base)->latest('published_at')->first();
            $featured = (clone $base)->where('is_featured', true)->whereKeyNot($headline?->id)->orderBy('carousel_order')->latest('published_at')->limit(5)->get();

            if ($featured->isEmpty()) {
                $featured = (clone $base)->whereKeyNot($headline?->id)->latest('published_at')->limit(5)->get();
            }

            return [
                'headline' => $headline?->id,
                'featured' => $featured->modelKeys(),
                'latest' => (clone $base)->whereKeyNot($headline?->id)->latest('published_at')->limit(10)->pluck('id')->all(),
                'popular' => (clone $base)->orderByDesc('views_count')->limit(5)->pluck('id')->all(),
                'breaking' => (clone $base)->where('is_breaking', true)->latest('published_at')->limit(8)->pluck('id')->all(),
                'categories' => Category::query()->where('is_active', true)->orderBy('order')->pluck('id')->all(),
            ];
        });

        return view('public.home', [
            'headline' => $contentIds['headline'] ? Article::query()->with(['author', 'category'])->find($contentIds['headline']) : null,
            'featured' => $this->articlesInOrder($contentIds['featured']),
            'latest' => $this->articlesInOrder($contentIds['latest']),
            'popular' => $this->articlesInOrder($contentIds['popular']),
            'breaking' => $this->articlesInOrder($contentIds['breaking']),
            'categories' => $this->categoriesInOrder($contentIds['categories']),
        ]);
    }

    /** @param array<int, int|string> $ids */
    private function articlesInOrder(array $ids): Collection
    {
        $articles = Article::query()->with(['author', 'category'])->whereKey($ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $articles->get($id))->filter()->values();
    }

    /** @param array<int, int|string> $ids */
    private function categoriesInOrder(array $ids): Collection
    {
        $categories = Category::query()->whereKey($ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $categories->get($id))->filter()->values();
    }
}
