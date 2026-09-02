<div>
    <span class="eyebrow">Interaksi pembaca</span><h1 class="mt-1 font-display text-3xl font-bold">Moderasi komentar</h1>
    <div class="admin-card mt-6">
        <label class="label" for="comment-status">Status</label>
        <div wire:ignore class="max-w-xs"><select id="comment-status" wire:model.live="status" data-tom-select data-placeholder="Semua status"><option value="pending">Menunggu</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option><option value="spam">Spam</option><option value="">Semua</option></select></div>
        <div class="mt-5 grid gap-4">@forelse($comments as $comment)<article wire:key="admin-comment-{{ $comment->id }}" class="rounded-xl border border-line p-4"><div class="flex flex-wrap justify-between gap-3"><div><strong>{{ $comment->name }}</strong><p class="text-xs text-muted">{{ $comment->email }} · {{ $comment->article->title }}</p></div><span class="status-pill">{{ $comment->status }}</span></div><p class="mt-3">{{ $comment->body }}</p><div class="mt-4 flex flex-wrap gap-2"><button wire:click="moderate({{ $comment->id }}, 'approved')" class="btn-secondary">Setujui</button><button wire:click="moderate({{ $comment->id }}, 'rejected')" class="btn-secondary">Tolak</button><button wire:click="moderate({{ $comment->id }}, 'spam')" class="btn-danger">Spam</button></div></article>@empty<p class="py-8 text-center text-muted">Tidak ada komentar pada status ini.</p>@endforelse</div>
        <div class="mt-5">{{ $comments->links() }}</div>
    </div>
</div>
