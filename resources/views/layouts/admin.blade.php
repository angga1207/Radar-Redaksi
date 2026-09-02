<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Redaksi' }} — Radar Redaksi</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark')
    </script>@vite(['resources/css/app.css', 'resources/js/app.js'])@livewireStyles
</head>

<body data-admin-panel class="bg-canvas text-ink antialiased">
    <div x-data="{ sidebar: false }" class="min-h-dvh lg:grid lg:grid-cols-[260px_1fr]">
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 z-40 flex h-dvh w-65 flex-col overflow-hidden bg-ink text-white transition-transform lg:sticky lg:top-0 lg:translate-x-0">
            <div class="shrink-0 border-b border-white/10 p-5">
                <div class="flex items-center justify-between gap-3"><a href="{{ route('home') }}" class="brand-logo text-white"><span>RADAR</span><b>REDAKSI</b></a><button type="button" @click="sidebar=false" class="icon-button text-slate-300 lg:hidden" aria-label="Tutup navigasi"><i class="fa-solid fa-xmark"></i></button></div>
                <p class="mt-1 text-xs text-slate-400">Ruang kendali redaksi</p>
                <a href="{{ route('admin.articles.create') }}" wire:navigate class="mt-5 flex min-h-11 items-center justify-center gap-2 rounded-lg bg-brand px-4 text-sm font-bold text-white transition hover:bg-[#d94712]"><i class="fa-solid fa-pen-nib"></i> Tulis berita</a>
            </div>
            <nav class="admin-sidebar-scroll flex-1 overflow-y-auto overscroll-contain px-3 py-4" aria-label="Navigasi panel admin">
                <div class="grid gap-1"><p class="admin-nav-label">Utama</p><a class="admin-nav" href="{{ route('admin.dashboard') }}" wire:navigate><i class="fa-solid fa-chart-line"></i> Ringkasan</a><a class="admin-nav" href="{{ route('admin.articles.index') }}" wire:navigate><i class="fa-regular fa-newspaper"></i> Berita</a><a class="admin-nav" href="{{ route('admin.media') }}" wire:navigate><i class="fa-solid fa-images"></i> Media</a></div>
                <div class="mt-5 grid gap-1"><p class="admin-nav-label">Konten portal</p>
                    @if(auth()->user()->hasPermission('taxonomy.manage'))<a class="admin-nav" href="{{ route('admin.taxonomy.index') }}" wire:navigate><i class="fa-solid fa-tags"></i> Kanal & tag</a>@endif
                    @if(auth()->user()->hasPermission('comments.manage'))<a class="admin-nav" href="{{ route('admin.comments') }}" wire:navigate><i class="fa-solid fa-comments"></i> Komentar</a>@endif
                    @if(auth()->user()->hasPermission('pages.manage'))<a class="admin-nav" href="{{ route('admin.pages.index') }}" wire:navigate><i class="fa-solid fa-file-lines"></i> Halaman</a>@endif
                    @if(auth()->user()->hasPermission('menus.manage'))<a class="admin-nav" href="{{ route('admin.menus') }}" wire:navigate><i class="fa-solid fa-bars"></i> Menu</a>@endif
                    @if(auth()->user()->hasPermission('advertisements.manage'))<a class="admin-nav" href="{{ route('admin.advertisements') }}" wire:navigate><i class="fa-solid fa-rectangle-ad"></i> Iklan</a>@endif
                </div>
                @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage') || auth()->user()->hasPermission('settings.manage') || auth()->user()->hasPermission('audit.view'))
                    <div class="mt-5 grid gap-1"><p class="admin-nav-label">Sistem</p>
                        @if(auth()->user()->hasPermission('users.manage'))<a class="admin-nav" href="{{ route('admin.users.index') }}" wire:navigate><i class="fa-solid fa-users-gear"></i> Pengguna</a>@endif
                        @if(auth()->user()->hasPermission('roles.manage'))<a class="admin-nav" href="{{ route('admin.roles') }}" wire:navigate><i class="fa-solid fa-user-shield"></i> Peran & akses</a>@endif
                        @if(auth()->user()->hasPermission('settings.manage'))<a class="admin-nav" href="{{ route('admin.settings') }}" wire:navigate><i class="fa-solid fa-gears"></i> Pengaturan</a>@endif
                        @if(auth()->user()->hasPermission('audit.view'))<a class="admin-nav" href="{{ route('admin.audit-logs') }}" wire:navigate><i class="fa-solid fa-shield-halved"></i> Audit log</a>@endif
                    </div>
                @endif
            </nav>
            <div class="shrink-0 border-t border-white/10 p-3"><form method="POST" action="{{ route('logout') }}">@csrf<button class="admin-nav w-full"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</button></form></div>
        </aside>
        <div>
            <header
                class="sticky top-0 z-30 flex min-h-16 items-center gap-3 border-b border-line bg-surface/95 px-4 backdrop-blur lg:px-8">
                <button @click="sidebar=!sidebar" class="icon-button lg:hidden" aria-label="Buka navigasi"><i
                        class="fa-solid fa-bars"></i></button>
                <div>
                    <p class="text-xs text-muted">Panel Admin</p>
                    <p class="font-semibold">Selamat bekerja, {{ auth()->user()->name }}</p>
                </div><button data-theme-toggle class="icon-button ml-auto" aria-label="Ganti tema"><i
                        class="fa-solid fa-circle-half-stroke"></i></button>
            </header>
            <main class="p-4 lg:p-8">{{ $slot }}</main>
        </div>
    </div>@livewireScripts
</body>

</html>
