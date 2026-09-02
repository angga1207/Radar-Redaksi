<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->status === 'published' && $page->published_at?->isPast(), 404);

        return view('public.page', compact('page'));
    }
}
