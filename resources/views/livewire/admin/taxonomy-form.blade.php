<div>
    <a href="{{ route('admin.taxonomy.index') }}" wire:navigate class="text-sm font-bold text-brand"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar</a>
    <span class="eyebrow mt-5 block">Struktur konten</span><h1 class="mt-1 font-display text-3xl font-bold">{{ ($category || $tag) ? 'Edit' : 'Tambah' }} {{ $type === 'tag' ? 'tag' : 'kanal' }}</h1>
    <form wire:submit="save" class="mt-6 grid gap-6 xl:grid-cols-[1fr_320px]">
        <div class="admin-card grid content-start gap-5">
            <div><label class="label" for="taxonomy-name">Nama *</label><input id="taxonomy-name" wire:model="name" class="field" autofocus>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            @if($type === 'category')
                <div><label class="label" for="taxonomy-description">Deskripsi</label><textarea id="taxonomy-description" wire:model="description" class="field min-h-32" maxlength="1000"></textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="taxonomy-parent">Kanal induk</label><div wire:ignore><select id="taxonomy-parent" wire:model="parentId" data-tom-select data-livewire-property="parentId" data-placeholder="Pilih kanal induk"><option value="">Tanpa induk</option>@foreach($categories as $parentCategory)<option value="{{ $parentCategory->id }}">{{ $parentCategory->name }}</option>@endforeach</select></div>@error('parentId')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="grid gap-5 md:grid-cols-2"><div><label class="label" for="taxonomy-icon">Ikon Font Awesome</label><input id="taxonomy-icon" wire:model="icon" class="field" placeholder="fa-solid fa-newspaper">@error('icon')<p class="field-error">{{ $message }}</p>@enderror</div><div><label class="label" for="taxonomy-order">Urutan</label><input id="taxonomy-order" type="number" min="0" max="999" wire:model="order" class="field">@error('order')<p class="field-error">{{ $message }}</p>@enderror</div></div>
            @else
                <p class="rounded-xl bg-muted p-4 text-sm text-muted">Slug URL tag dibuat otomatis berdasarkan nama saat disimpan.</p>
            @endif
        </div>
        <aside class="admin-card grid content-start gap-4">
            <h2 class="font-bold">Pengaturan</h2>
            @if($type === 'category')<div><label class="label" for="taxonomy-color">Warna kanal</label><input id="taxonomy-color" type="color" wire:model="color" class="field min-h-12">@error('color')<p class="field-error">{{ $message }}</p>@enderror</div><label class="flex min-h-11 items-center gap-2"><input type="checkbox" wire:model="isActive"> Kanal aktif</label>@endif
            <button class="btn-primary w-full" wire:loading.attr="disabled"><span wire:loading.remove>Simpan {{ $type === 'tag' ? 'tag' : 'kanal' }}</span><span wire:loading>Menyimpan...</span></button>
        </aside>
    </form>
</div>
