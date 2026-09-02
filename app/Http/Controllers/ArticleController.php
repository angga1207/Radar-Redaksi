<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ArticleController extends Controller
{
    public function show(Request $request, Article $article): View
    {
        abort_unless($article->status === ArticleStatus::Published && $article->published_at?->isPast(), 404);
        $article->load(['author', 'category', 'tags']);
        $viewToken = $request->session()->get('article_view_token');
        if (! is_string($viewToken)) {
            $viewToken = Str::random(40);
            $request->session()->put('article_view_token', $viewToken);
        }
        $view = ArticleView::query()->firstOrCreate([
            'article_id' => $article->id,
            'session_hash' => hash('sha256', $viewToken),
            'viewed_on' => today(),
        ], ['ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 'user_agent' => $request->userAgent()]);
        if ($view->wasRecentlyCreated) {
            $article->increment('views_count');
        }

        return view('public.article', [
            'article' => $article,
            'related' => Article::query()->published()->with(['author', 'category'])
                ->where('category_id', $article->category_id)->whereKeyNot($article->id)
                ->latest('published_at')->limit(4)->get(),
        ]);
    }

    public function category(Category $category): View
    {
        return view('public.archive', [
            'title' => $category->name,
            'description' => $category->description,
            'articles' => Article::query()->published()->with(['author', 'category'])
                ->whereBelongsTo($category)->latest('published_at')->paginate(12),
        ]);
    }

    public function latest(): View
    {
        return view('public.archive', [
            'title' => 'Berita Terbaru', 'description' => 'Kabar paling aktual dari Radar Redaksi.',
            'articles' => Article::query()->published()->with(['author', 'category'])->latest('published_at')->paginate(12),
        ]);
    }
}
