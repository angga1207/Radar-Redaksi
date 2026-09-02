<?php

namespace App\Livewire\Admin;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleView;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        $viewsByDate = ArticleView::query()->whereDate('viewed_on', '>=', today()->subDays(6))->get(['viewed_on'])->countBy(fn (ArticleView $view): string => $view->viewed_on->format('Y-m-d'));
        $viewTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($viewsByDate): array {
            $date = today()->subDays($daysAgo);

            return ['label' => $date->locale('id')->translatedFormat('D'), 'date' => $date->format('Y-m-d'), 'count' => $viewsByDate->get($date->format('Y-m-d'), 0)];
        });
        $maxTrendViews = max(1, (int) $viewTrend->max('count'));

        return view('livewire.admin.dashboard', [
            'counts' => [
                'draft' => Article::query()->where('status', ArticleStatus::Draft)->count(),
                'review' => Article::query()->where('status', ArticleStatus::InReview)->count(),
                'publishedToday' => Article::query()->published()->whereDate('published_at', today())->count(),
                'totalViews' => Article::query()->sum('views_count'),
            ],
            'recent' => Article::query()->with(['author', 'category'])->latest()->limit(6)->get(),
            'popular' => Article::query()->published()->with('category')->orderByDesc('views_count')->limit(5)->get(),
            'viewTrend' => $viewTrend,
            'maxTrendViews' => $maxTrendViews,
        ]);
    }
}
