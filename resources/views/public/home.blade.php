@extends('layouts.public')

@section('content')
    @if ($breaking->isNotEmpty())
        <section class="relative z-30 overflow-hidden bg-brand text-white" aria-label="Radar terkini">
            <div class="site-container flex min-h-12 items-center gap-4">
                <strong class="flex shrink-0 items-center gap-2 text-[11px] uppercase tracking-[.18em]"><span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-white/70"></span><span class="relative size-2.5 rounded-full bg-white"></span></span>Radar terkini</strong>
                <span class="hidden h-5 w-px bg-white/35 sm:block"></span>
                <div class="swiper breaking-swiper min-w-0 flex-1"><div class="swiper-wrapper">@foreach ($breaking as $item)<a class="swiper-slide flex items-center truncate text-sm font-semibold" href="{{ route('articles.show', $item) }}">{{ $item->title }}</a>@endforeach</div></div>
                @if ($breaking->count() > 1)<button type="button" class="grid size-9 shrink-0 place-items-center rounded-full border border-white/40 transition hover:bg-white/15" data-carousel-toggle="breaking" aria-label="Jeda Radar Terkini" aria-pressed="false"><i class="fa-solid fa-pause" aria-hidden="true"></i></button>@endif
            </div>
        </section>
    @endif

    <div class="site-container py-6 sm:py-8 lg:py-10">
        @if ($advertisement = collect($advertisements['homepage_top'] ?? [])->first())
            <aside class="mb-7" aria-label="Iklan" data-aos="fade-down"><span class="mb-1 block text-center text-[10px] font-bold uppercase tracking-widest text-muted">Iklan</span><a href="{{ route('advertisements.click', $advertisement['id']) }}" target="_blank" rel="noopener sponsored"><img src="{{ route('advertisements.image', $advertisement['id']) }}" alt="{{ $advertisement['title'] }}" class="mx-auto max-h-40 w-full rounded-2xl border border-line bg-surface object-cover" loading="lazy"></a></aside>
        @endif

        @if ($categories->isNotEmpty())
            <nav class="mb-6 flex gap-2 overflow-x-auto pb-2" aria-label="Jelajahi kanal" data-aos="fade-up"><span class="flex min-h-10 shrink-0 items-center pr-2 text-xs font-black uppercase tracking-[.16em] text-muted">Jelajahi</span>@foreach ($categories->take(8) as $category)<a class="category-pill" href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>@endforeach</nav>
        @endif

        @if ($headline)
            <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(330px,.72fr)]" aria-labelledby="headline-title">
                <article class="news-hero group relative min-h-[430px] overflow-hidden rounded-[1.75rem] bg-slate-950 shadow-xl shadow-slate-950/15 sm:min-h-[560px]" data-aos="fade-up">
                    <img src="{{ $headline->featured_image ?: asset('images/news-placeholder.svg') }}" alt="{{ $headline->image_alt ?: $headline->title }}" class="news-card-image absolute inset-0 -z-10 size-full object-cover" width="1100" height="700">
                    <div class="absolute left-5 top-5 flex items-center gap-2 rounded-full border border-white/25 bg-black/25 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.18em] text-white backdrop-blur-md sm:left-8 sm:top-8"><i class="fa-solid fa-bolt text-brand"></i> Headline hari ini</div>
                    <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-8 lg:p-10">
                        <a href="{{ route('categories.show', $headline->category) }}" class="badge">{{ $headline->category->name }}</a>
                        <h1 id="headline-title" class="mt-4 max-w-4xl font-display text-[2.25rem] font-bold leading-[1.02] text-white sm:text-5xl lg:text-6xl"><a class="decoration-brand decoration-4 underline-offset-8 hover:underline" href="{{ route('articles.show', $headline) }}">{{ $headline->title }}</a></h1>
                        <div class="mt-5 flex flex-wrap items-center gap-4 text-sm text-slate-200"><span><i class="fa-regular fa-clock mr-1.5 text-brand"></i>{{ $headline->published_at->locale('id')->diffForHumans() }}</span><span><i class="fa-regular fa-eye mr-1.5 text-brand"></i>{{ number_format($headline->views_count) }} dibaca</span></div>
                        <p class="mt-4 max-w-2xl line-clamp-2 text-sm leading-relaxed text-slate-200 sm:text-base">{{ $headline->excerpt }}</p>
                    </div>
                </article>

                <div class="public-panel min-w-0 p-5 sm:p-6" data-aos="fade-left" data-aos-delay="100">
                    <div class="section-heading"><div><span class="eyebrow">Kurasi meja redaksi</span><h2>Pilihan untuk Anda</h2></div><span class="grid size-10 place-items-center rounded-full bg-brand/10 text-brand"><i class="fa-regular fa-bookmark"></i></span></div>
                    @if ($featured->isNotEmpty())
                        <div class="swiper hero-swiper mt-5 min-w-0 overflow-hidden"><div class="swiper-wrapper">
                            @foreach ($featured->take(5) as $item)
                                <article class="swiper-slide group"><a href="{{ route('articles.show', $item) }}" class="block overflow-hidden rounded-2xl bg-muted"><img src="{{ $item->featured_image ?: asset('images/news-placeholder.svg') }}" alt="Thumbnail: {{ $item->image_alt ?: $item->title }}" class="news-card-image aspect-video w-full object-cover" width="640" height="360" loading="lazy"></a><div class="pt-4"><a href="{{ route('categories.show', $item->category) }}" class="eyebrow">{{ $item->category->name }}</a><h3 class="mt-2 line-clamp-3 font-display text-2xl font-bold leading-tight text-ink"><a class="hover:text-brand" href="{{ route('articles.show', $item) }}">{{ $item->title }}</a></h3><p class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted">{{ $item->excerpt }}</p></div></article>
                            @endforeach
                        </div><div class="flex items-center justify-between gap-3"><div class="swiper-pagination !relative !bottom-auto mt-5"></div>@if ($featured->count() > 1)<button type="button" class="icon-button mt-4 shrink-0" data-carousel-toggle="hero" aria-label="Jeda carousel pilihan" aria-pressed="false"><i class="fa-solid fa-pause" aria-hidden="true"></i></button>@endif</div></div>
                    @else
                        <p class="py-8 text-sm text-muted">Berita pilihan sedang disiapkan redaksi.</p>
                    @endif
                </div>
            </section>
        @else
            <section class="public-panel px-6 py-20 text-center" data-aos="zoom-in"><span class="eyebrow">Radar Redaksi</span><h1 class="mt-2 font-display text-4xl font-bold text-ink">Berita terbaru sedang disiapkan</h1><p class="mx-auto mt-3 max-w-xl text-muted">Kunjungi kembali untuk membaca kabar terverifikasi dari meja redaksi.</p></section>
        @endif

        <section class="mt-12 grid items-start gap-8 lg:mt-16 lg:grid-cols-[minmax(0,1fr)_350px] lg:gap-12">
            <div>
                <div class="section-heading" data-aos="fade-up"><div><span class="eyebrow">Kronologi hari ini</span><h2>Berita terbaru</h2></div><a href="{{ route('articles.latest') }}">Lihat semua <i class="fa-solid fa-arrow-right"></i></a></div>
                <div class="mt-3 divide-y divide-line">@forelse ($latest as $item)<x-article-card :article="$item" horizontal class="group py-6" data-aos="fade-up" data-aos-stagger />@empty<p class="rounded-xl bg-surface p-6 text-muted">Belum ada berita terbaru.</p>@endforelse</div>
            </div>

            <aside class="grid content-start gap-6">
                @if ($advertisement = collect($advertisements['homepage_sidebar'] ?? [])->first())
                    <div aria-label="Iklan" data-aos="fade-left"><span class="mb-1 block text-center text-[10px] font-bold uppercase tracking-widest text-muted">Iklan</span><a href="{{ route('advertisements.click', $advertisement['id']) }}" target="_blank" rel="noopener sponsored"><img src="{{ route('advertisements.image', $advertisement['id']) }}" alt="{{ $advertisement['title'] }}" class="w-full rounded-2xl border border-line bg-surface object-cover" loading="lazy"></a></div>
                @endif
                <div class="public-panel p-5 sm:p-6 lg:sticky lg:top-5" data-aos="fade-left" data-aos-delay="100">
                    <div class="section-heading"><div><span class="eyebrow">Sedang ramai</span><h2>Terpopuler</h2></div><i class="fa-solid fa-arrow-trend-up text-xl text-brand"></i></div>
                    <ol class="mt-2 divide-y divide-line">@forelse ($popular as $item)<li class="group grid grid-cols-[52px_1fr] gap-3 py-5"><span class="story-index font-display text-4xl font-black leading-none">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><a class="font-display text-lg font-bold leading-tight text-ink transition hover:text-brand" href="{{ route('articles.show', $item) }}">{{ $item->title }}</a><p class="mt-2 text-xs text-muted">{{ number_format($item->views_count) }} pembaca</p></div></li>@empty<li class="py-6 text-sm text-muted">Data berita populer belum tersedia.</li>@endforelse</ol>
                    <a href="{{ route('articles.popular') }}" class="btn-secondary mt-3 w-full gap-2">Lihat berita populer <i class="fa-solid fa-arrow-right text-brand"></i></a>
                </div>
            </aside>
        </section>
    </div>
@endsection
