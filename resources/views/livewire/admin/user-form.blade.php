<div>
    <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm font-bold text-brand"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar</a>
    <span class="eyebrow mt-5 block">Akses redaksi</span><h1 class="mt-1 font-display text-3xl font-bold">{{ $user ? 'Edit pengguna' : 'Tambah pengguna' }}</h1>
    <form wire:submit="save" class="mt-6 grid gap-6 xl:grid-cols-[1fr_320px]">
        <div class="admin-card grid gap-5">
            <div class="grid gap-5 md:grid-cols-2">
                @foreach([['name', 'Nama', 'text'], ['username', 'Username', 'text'], ['email', 'Email', 'email'], ['password', $user ? 'Kata sandi baru (opsional)' : 'Kata sandi', 'password']] as [$model, $label, $inputType])<div><label class="label" for="user-{{ $model }}">{{ $label }}</label><input id="user-{{ $model }}" type="{{ $inputType }}" wire:model="{{ $model }}" class="field">@error($model)<p class="field-error">{{ $message }}</p>@enderror</div>@endforeach
            </div>
            <div><label class="label" for="user-bio">Bio penulis</label><textarea id="user-bio" wire:model="bio" class="field min-h-36" maxlength="1000" placeholder="Profil singkat yang tampil di halaman penulis"></textarea>@error('bio')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="user-avatar">Avatar</label><input id="user-avatar" type="file" wire:model="avatarUpload" accept="image/jpeg,image/png,image/webp" class="field">@error('avatarUpload')<p class="field-error">{{ $message }}</p>@enderror<div wire:loading wire:target="avatarUpload" class="mt-2 text-sm text-muted">Mengunggah avatar...</div>@if($avatarUpload)<img src="{{ $avatarUpload->temporaryUrl() }}" alt="Pratinjau avatar" class="mt-3 size-24 rounded-full object-cover">@elseif($avatar)<img src="{{ $avatar }}" alt="Avatar saat ini" class="mt-3 size-24 rounded-full object-cover">@endif</div>
        </div>
        <aside class="admin-card grid content-start gap-4">
            <h2 class="font-bold">Akses akun</h2>
            <div><label class="label" for="role">Peran</label><div wire:ignore><select id="role" wire:model="role" data-tom-select data-livewire-property="role">@foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'editor' => 'Editor', 'reporter' => 'Reporter', 'contributor' => 'Kontributor'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>@error('role')<p class="field-error">{{ $message }}</p>@enderror</div>
            <label class="flex min-h-11 items-center gap-2"><input type="checkbox" wire:model="isActive"> Akun aktif</label>
            <button class="btn-primary w-full" wire:loading.attr="disabled"><span wire:loading.remove>Simpan pengguna</span><span wire:loading>Menyimpan...</span></button>
        </aside>
    </form>
</div>
