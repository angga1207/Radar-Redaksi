<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class ArchiveController extends Controller
{
    public function popular(): View
    {
        return $this->archive('Terpopuler', 'Berita yang paling banyak dibaca.', Article::query()->published()->with(['author', 'category'])->orderByDesc('views_count')->paginate(12));
    }

    public function tag(Tag $tag): View
    {
        return $this->archive("Tag: {$tag->name}", 'Kumpulan berita dengan topik terkait.', $tag->articles()->published()->with(['author', 'category'])->latest('published_at')->paginate(12));
    }

    public function author(User $user): View
    {
        return view('public.archive', [
            'title' => "Berita oleh {$user->name}",
            'description' => $user->bio ?: 'Profil dan karya penulis Radar Redaksi.',
            'articles' => $user->articles()->published()->with(['author', 'category'])->latest('published_at')->paginate(12),
            'author' => $user,
        ]);
    }

    public function photos(): View
    {
        return $this->archive('Berita Foto', 'Kabar pilihan dalam rangkaian visual.', Article::query()->published()->where('content_type', 'photo')->with(['author', 'category'])->latest('published_at')->paginate(12));
    }

    public function videos(): View
    {
        return $this->archive('Berita Video', 'Laporan dan peristiwa dalam format video.', Article::query()->published()->where('content_type', 'video')->with(['author', 'category'])->latest('published_at')->paginate(12));
    }

    private function archive(string $title, string $description, $articles): View
    {
        return view('public.archive', compact('title', 'description', 'articles'));
    }
}
