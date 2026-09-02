<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        return response()->view('public.sitemap', [
            'articles' => Article::query()->published()->select(['slug', 'updated_at'])->latest('published_at')->get(),
            'categories' => Category::query()->where('is_active', true)->get(['slug', 'updated_at']),
            'pages' => Page::query()->where('status', 'published')->where('published_at', '<=', now())->get(['slug', 'updated_at']),
        ])->header('Content-Type', 'application/xml');
    }
}
