<div>
    <div class="flex flex-wrap items-end justify-between gap-4"><div><span class="eyebrow">Informasi portal</span><h1 class="mt-1 font-display text-3xl font-bold">Daftar halaman</h1></div><a href="{{ route('admin.pages.create') }}" wire:navigate class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah halaman</a></div>
    @if(session('success'))<p class="mt-4 rounded-lg bg-green-100 p-3 text-green-900">{{ session('success') }}</p>@endif
    <div class="admin-card mt-6"><label class="label" for="page-search">Cari halaman</label><input id="page-search" wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Judul atau slug">
        <div class="mt-5 overflow-x-auto"><table class="data-table"><thead><tr><th>Judul</th><th>URL</th><th>Status</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
        @forelse($pages as $page)<tr wire:key="page-{{ $page->id }}"><td class="font-bold">{{ $page->title }}</td><td class="text-muted">/halaman/{{ $page->slug }}</td><td><span class="status-pill">{{ $page->status }}</span></td><td>{{ $page->updated_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="btn-secondary">Edit</a></td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-muted">Belum ada halaman.</td></tr>@endforelse
        </tbody></table></div><div class="mt-5">{{ $pages->links() }}</div></div>
</div>
