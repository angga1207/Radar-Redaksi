<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><span class="eyebrow">Akses redaksi</span><h1 class="mt-1 font-display text-3xl font-bold">Daftar pengguna</h1></div>
        <a href="{{ route('admin.users.create') }}" wire:navigate class="btn-primary"><i class="fa-solid fa-user-plus"></i> Tambah pengguna</a>
    </div>
    @if(session('success'))<p class="mt-4 rounded-lg bg-green-100 p-3 text-green-900">{{ session('success') }}</p>@endif
    <div class="admin-card mt-6">
        <label class="label" for="user-search">Cari pengguna</label>
        <input id="user-search" wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Nama, username, atau email">
        <div class="mt-5 overflow-x-auto"><table class="data-table"><thead><tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse($users as $user)<tr wire:key="user-{{ $user->id }}"><td><div class="flex items-center gap-3">@if($user->avatar)<img src="{{ $user->avatar }}" alt="" class="size-10 rounded-full object-cover">@else<div class="grid size-10 place-items-center rounded-full bg-muted font-bold text-brand">{{ str($user->name)->substr(0, 1)->upper() }}</div>@endif<div><strong>{{ $user->name }}</strong><p class="text-xs text-muted">{{ $user->email }}</p></div></div></td><td>{{ str($user->role)->replace('_', ' ')->title() }}</td><td><span class="status-pill">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><a href="{{ route('admin.users.edit', $user) }}" wire:navigate class="btn-secondary">Edit</a></td></tr>
            @empty<tr><td colspan="4" class="py-10 text-center text-muted">Pengguna tidak ditemukan.</td></tr>@endforelse
        </tbody></table></div><div class="mt-5">{{ $users->links() }}</div>
    </div>
</div>
