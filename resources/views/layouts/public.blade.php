<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F15A24">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="{{ $siteSettings['site_favicon'] ?? asset('favicon.ico') }}">
    <title>@yield('title', $siteSettings['seo_title'] ?? 'Radar Redaksi — Kabar Terverifikasi')</title>
    <meta name="description" content="@yield('meta_description', $siteSettings['seo_description'] ?? 'Berita Indonesia terbaru, tepercaya, dan mudah dipahami.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('og_title', 'Radar Redaksi — Kabar Terverifikasi')">
    <meta property="og:description" content="@yield('meta_description', 'Berita Indonesia terbaru, tepercaya, dan mudah dipahami.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:image" content="@yield('og_image', $siteSettings['site_logo'] ?? asset('favicon.ico'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Radar Redaksi — Kabar Terverifikasi')">
    <meta name="twitter:description" content="@yield('meta_description', 'Berita Indonesia terbaru, tepercaya, dan mudah dipahami.')">
    <meta name="twitter:image" content="@yield('og_image', $siteSettings['site_logo'] ?? asset('favicon.ico'))">
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia(
                '(prefers-color-scheme:dark)').matches)) document.documentElement.classList.add('dark')
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>

<body data-public-shell class="min-h-dvh overflow-x-hidden bg-canvas text-ink antialiased">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <header x-data="{ open: false }" class="relative z-40 border-b border-line bg-surface">
        <div class="bg-ink text-white">
            <div class="site-container flex min-h-9 items-center justify-between text-xs">
                <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand">
                    Panel Redaksi
                </a>
            </div>
        </div>
        <div class="site-container flex min-h-20 items-center gap-4">
            <button @click="open=!open" class="icon-button lg:hidden" aria-label="Buka menu"><i
                    class="fa-solid fa-bars"></i></button>
            <a href="{{ route('home') }}" class="brand-logo" aria-label="{{ $siteSettings['site_name'] ?? 'Radar Redaksi' }} beranda">@if($siteSettings['site_logo'] ?? null)<img src="{{ $siteSettings['site_logo'] }}" alt="{{ $siteSettings['site_name'] ?? 'Radar Redaksi' }}" class="max-h-12 w-auto object-contain">@else<span>RADAR</span><b>REDAKSI</b>@endif</a>
            <form action="{{ route('search') }}" class="ml-auto hidden w-full max-w-md md:flex"><label
                    for="header-search" class="sr-only">Cari berita</label><input id="header-search" name="q"
                    class="field rounded-r-none" placeholder="Cari topik, tokoh, atau lokasi"><button
                    class="btn-primary rounded-l-none" aria-label="Cari"><i
                        class="fa-solid fa-magnifying-glass"></i></button></form>
            <button data-theme-toggle class="icon-button" aria-label="Ganti tema"><i
                    class="fa-solid fa-moon dark:hidden"></i><i class="fa-solid fa-sun hidden dark:block"></i></button>
        </div>
        <nav class="border-t border-line" aria-label="Kanal berita">
            <div class="site-container flex items-center gap-1 overflow-x-auto py-1"><a class="nav-link"
                    href="{{ route('articles.latest') }}">Terbaru</a><a class="nav-link"
                    href="{{ route('articles.popular') }}">Terpopuler</a>
                    <a class="nav-link" href="{{ route('articles.photos') }}">Foto</a><a class="nav-link" href="{{ route('articles.videos') }}">Video</a>
                @foreach ($navCategories as $navCategory)
                    <a class="nav-link"
                        href="{{ route('categories.show', $navCategory['slug']) }}">{{ $navCategory['name'] }}</a>
                    @endforeach @foreach ($publicMenus['header'] ?? [] as $item)
                    @if(count($item['children']))
                        <details class="group relative shrink-0"><summary class="nav-link cursor-pointer list-none">{{ $item['label'] }} <i class="fa-solid fa-chevron-down text-[10px]"></i></summary><div class="absolute left-0 top-full z-50 mt-1 min-w-52 rounded-xl border border-line bg-surface p-2 shadow-xl">@foreach($item['children'] as $child)<a class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-muted hover:text-brand" href="{{ $child['url'] }}" target="{{ $child['target'] }}" @if($child['target'] === '_blank') rel="noopener" @endif>{{ $child['label'] }}</a>@endforeach</div></details>
                    @else
                        <a class="nav-link" href="{{ $item['url'] }}" target="{{ $item['target'] }}"
                            @if ($item['target'] === '_blank') rel="noopener" @endif>{{ $item['label'] }}</a>
                    @endif
                    @endforeach
            </div>
        </nav>
        <div x-show="open" x-cloak class="site-container border-t border-line py-3 lg:hidden">
            <form action="{{ route('search') }}" class="flex"><input name="q" class="field rounded-r-none"
                    placeholder="Cari berita"><button class="btn-primary rounded-l-none">Cari</button></form>
        </div>
    </header>
    <div class="hidden border-b border-amber-300 bg-amber-50 px-4 py-3 text-center text-sm font-semibold text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100" wire:offline.class.remove="hidden" role="status">Koneksi terputus. Konten yang sudah terbuka tetap dapat dibaca; perubahan akan dilanjutkan setelah tersambung.</div>
    <main id="main-content">{{ $slot ?? '' }}@yield('content')</main>
    <footer class="mt-16 border-t border-line bg-ink py-12 text-white">
        <div class="site-container grid gap-8 md:grid-cols-3">
            <div data-aos="fade-up" data-aos-delay="40">
                <div class="brand-logo text-white">@if($siteSettings['site_logo'] ?? null)<img src="{{ $siteSettings['site_logo'] }}" alt="{{ $siteSettings['site_name'] ?? 'Radar Redaksi' }}" class="max-h-12 max-w-52 object-contain">@else<span>RADAR</span><b>REDAKSI</b>@endif</div>
                <p class="mt-3 max-w-sm text-sm text-slate-300">
                    {{ $siteSettings['tagline'] ?? 'Mengabarkan fakta dengan jernih, cepat, dan bertanggung jawab.' }}
                </p>
            </div>
            <div data-aos="fade-up" data-aos-delay="100">
                <h2 class="font-bold">Informasi</h2>
                <div class="mt-3 grid gap-2 text-sm text-slate-300">
                    @forelse($publicMenus['footer'] ?? [] as $item)
                        <a href="{{ $item['url'] }}" target="{{ $item['target'] }}"
                        @if ($item['target'] === '_blank') rel="noopener" @endif>{{ $item['label'] }}</a>@foreach($item['children'] as $child)<a class="pl-3 text-slate-400" href="{{ $child['url'] }}" target="{{ $child['target'] }}" @if($child['target'] === '_blank') rel="noopener" @endif>↳ {{ $child['label'] }}</a>@endforeach @empty<a
                            href="{{ route('pages.show', 'tentang') }}">Tentang Kami</a><a
                            href="{{ route('pages.show', 'pedoman-media-siber') }}">Pedoman Media Siber</a><a
                            href="{{ route('pages.show', 'kontak') }}">Kontak Redaksi</a>
                    @endforelse
                </div>
            </div>
            <div data-aos="fade-up" data-aos-delay="160">
                <h2 class="font-bold">Ikuti pembaruan</h2>
                <p class="mt-3 text-sm text-slate-300">Berita pilihan langsung dari meja redaksi.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach(['facebook' => ['fa-facebook-f', 'Facebook'], 'instagram' => ['fa-instagram', 'Instagram'], 'youtube' => ['fa-youtube', 'YouTube']] as $network => [$icon, $label])
                        @if($siteSettings[$network] ?? null)<a href="{{ $siteSettings[$network] }}" target="_blank" rel="noopener" class="icon-button border-white/20 text-white hover:text-brand" aria-label="{{ $label }} {{ $siteSettings['site_name'] ?? 'Radar Redaksi' }}"><i class="fa-brands {{ $icon }}"></i></a>@endif
                    @endforeach
                </div>
                @if($siteSettings['contact_email'] ?? null)<a href="mailto:{{ $siteSettings['contact_email'] }}" class="mt-4 inline-block text-sm text-slate-300 hover:text-brand">{{ $siteSettings['contact_email'] }}</a>@endif
            </div>
        </div>
        <div class="site-container mt-8 border-t border-white/15 pt-5 text-xs text-slate-400">© {{ date('Y') }}
            {{ $siteSettings['site_name'] ?? 'Radar Redaksi' }}. Hak cipta dilindungi.</div>
    </footer>
    @livewireScripts
</body>

</html>
