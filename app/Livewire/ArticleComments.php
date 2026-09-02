<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ArticleComments extends Component
{
    #[Locked]
    public Article $article;

    public string $name = '';

    public string $email = '';

    public string $body = '';

    public function submit(): void
    {
        abort_unless($this->article->allow_comments, 403);
        $rateLimitKey = 'comment:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages(['body' => 'Terlalu banyak komentar. Coba lagi dalam '.RateLimiter::availableIn($rateLimitKey).' detik.']);
        }
        $validated = $this->validate(['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255'], 'body' => ['required', 'string', 'min:10', 'max:2000']]);
        Comment::query()->create([...$validated, 'article_id' => $this->article->id, 'user_id' => auth()->id(), 'status' => 'pending', 'ip_hash' => hash_hmac('sha256', (string) request()->ip(), (string) config('app.key')), 'user_agent' => request()->userAgent()]);
        $this->reset(['body']);
        RateLimiter::hit($rateLimitKey, 60);
        session()->flash('comment-success', 'Komentar terkirim dan menunggu moderasi.');
    }

    public function render(): View
    {
        return view('livewire.article-comments', ['comments' => $this->article->comments()->where('status', 'approved')->latest()->get()]);
    }
}
