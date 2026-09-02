<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PreviewArticleController extends Controller
{
    public function __invoke(Article $article): View
    {
        Gate::authorize('view', $article);
        $article->load(['author', 'category', 'tags']);

        return view('public.article', ['article' => $article, 'related' => collect(), 'isPreview' => true]);
    }
}
