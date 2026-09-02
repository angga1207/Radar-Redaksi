<div class="site-container py-10">
    <span class="eyebrow">Temukan informasi</span>
    <h1 class="mt-2 font-display text-4xl font-bold">Pencarian berita</h1>
    <div class="mt-6 max-w-2xl">
        <label for="news-search" class="label">Kata kunci</label>
        <input id="news-search" wire:model.live.debounce.350ms="query" class="field" placeholder="Ketik minimal 2 karakter...">
    </div>

    <div wire:loading class="mt-8 text-muted" role="status">Mencari berita...</div>
    <div wire:loading.remove class="mt-9 grid max-w-4xl gap-7">
        @forelse($articles as $article)
            <x-article-card :article="$article" horizontal />
        @empty
            @if(str($query)->trim()->length() >= 2)
                <div class="rounded-xl border border-line bg-surface p-8 text-center">
                    <h2 class="font-bold">Berita tidak ditemukan</h2>
                    <p class="mt-1 text-sm text-muted">Coba kata kunci yang lebih umum.</p>
                </div>
            @endif
        @endforelse
    </div>

    @if($articles->hasPages())
        <div class="mt-8">{{ $articles->links() }}</div>
    @endif
</div>
