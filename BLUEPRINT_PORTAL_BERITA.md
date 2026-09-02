# Blueprint Portal Berita — Radar Redaksi

> Status: **v1 selesai — workflow inti dan hardening dasar terimplementasi**  
> Stack: Laravel, Livewire, Tailwind CSS, PostgreSQL, Alpine.js, Swiper Carousel, Tom Select, AOS  
> Bahasa antarmuka: Indonesia  
> Area aplikasi: Portal Publik + Panel Admin

## 1. Visi Produk

Radar Redaksi adalah portal berita Indonesia yang cepat, tepercaya, mudah dipindai, dan nyaman dibaca di perangkat apa pun. Pengalaman informasinya mengambil inspirasi dari pola portal berita besar seperti Detik.com—headline kuat, kanal mudah dijangkau, berita terbaru kronologis, dan trending yang terlihat—tanpa menyalin merek, aset, atau susunan visual secara identik.

Tujuan utama:

- Pembaca menemukan berita penting dalam kurang dari tiga interaksi.
- Redaksi dapat menulis, meninjau, menjadwalkan, dan menerbitkan berita dari satu panel.
- Light dan dark mode memiliki kualitas serta kontras yang setara.
- Struktur data siap berkembang untuk SEO, iklan, multimedia, dan workflow redaksi.

## 2. Peran dan Hak Akses

| Peran | Kemampuan utama |
|---|---|
| Super Admin | Seluruh konfigurasi, pengguna, peran, konten, audit, dan penghapusan permanen |
| Admin | Kelola konten, kategori, tag, media, komentar, dan pengaturan portal |
| Editor | Review, revisi, jadwalkan, terbitkan, arsipkan berita |
| Reporter | Buat dan ubah draft milik sendiri, kirim untuk review |
| Kontributor | Buat draft terbatas dan unggah media |
| Pembaca | Membaca, mencari, membagikan, dan mengirim komentar bila fitur diaktifkan |

Otorisasi wajib memakai Policy/Gate di server; menyembunyikan tombol di UI bukan pengamanan.

## 3. Arsitektur Informasi

### Portal publik

- `/` — beranda
- `/terbaru` — feed berita terbaru
- `/terpopuler` — berita populer
- `/kanal/{category:slug}` — halaman kanal/kategori
- `/berita/{article:slug}` — detail berita
- `/tag/{tag:slug}` — arsip tag
- `/penulis/{user:username}` — profil dan berita penulis
- `/cari?q=` — pencarian
- `/foto`, `/video` — konten multimedia
- `/tentang`, `/redaksi`, `/pedoman-media-siber`, `/privasi`, `/kontak` — halaman statis

### Panel admin

- `/admin` — ringkasan performa dan pekerjaan redaksi
- `/admin/articles` — tabel, filter, bulk action, status, dan kepemilikan berita
- `/admin/articles/create`, `/admin/articles/{article}/edit` — editor berita
- `/admin/categories`, `/admin/tags` — taksonomi
- `/admin/media` — pustaka media
- `/admin/comments` — moderasi komentar
- `/admin/users`, `/admin/roles` — pengguna dan akses
- `/admin/pages`, `/admin/menus`, `/admin/advertisements` — konten pendukung
- `/admin/settings` — identitas situs, SEO, sosial, dan preferensi
- `/admin/audit-logs` — jejak perubahan

## 4. Modul Fungsional

### 4.1 Publik

1. Header responsif: logo, tanggal, kanal, pencarian, dan pemilih tema.
2. **Radar Terkini**: breaking-news strip yang bergerak halus dan dapat dihentikan.
3. Hero carousel: berita pilihan dengan fallback statis bila JavaScript gagal.
4. Grid berita utama, terbaru, pilihan editor, dan terpopuler.
5. Detail artikel: breadcrumb, judul, ringkasan, penulis, tanggal, gambar, isi, tag, share, berita terkait.
6. Pencarian dan filter kanal dengan pagination server-side.
7. View counter unik berbasis sesi/IP hash dengan throttling.
8. SEO: canonical, Open Graph, Twitter Card, JSON-LD NewsArticle, sitemap, robots.
9. State lengkap: loading skeleton, kosong, error, dan offline/degraded message.

### 4.2 Redaksi/Admin

1. Dashboard statistik: draft, menunggu review, terbit hari ini, dan artikel terpopuler.
2. CRUD artikel melalui Livewire, autosave draft, validasi inline, preview, featured image.
3. Workflow: `draft -> in_review -> scheduled/published -> archived`, dengan riwayat transisi.
4. Tom Select untuk kategori, tag, reporter, dan relasi artikel.
5. Penjadwalan penerbitan melalui scheduler/queue.
6. Pustaka media dengan alt text, caption, kredit, dimensi, dan optimasi gambar.
7. Moderasi komentar: pending, approved, rejected, spam.
8. Pengaturan headline, featured, breaking, urutan carousel, dan menu.
9. Audit log untuk aksi penting dan perubahan status.

## 5. Model Data PostgreSQL

| Entitas | Kolom penting |
|---|---|
| users | name, username, email, password, avatar, bio, is_active, last_login_at |
| roles / permissions | name, guard_name; pivot role-user dan permission-role |
| categories | parent_id, name, slug, description, color, icon, order, is_active |
| tags | name, slug |
| articles | author_id, editor_id, category_id, title, slug, excerpt, body, status, featured_image, image_alt, image_caption, image_credit, is_featured, is_headline, is_breaking, allow_comments, published_at, scheduled_at, views_count, seo fields, timestamps, soft delete |
| article_tag | article_id, tag_id |
| article_revisions | article_id, user_id, title, excerpt, body, change_note, created_at |
| article_status_histories | article_id, actor_id, from_status, to_status, note, created_at |
| comments | article_id, user_id nullable, parent_id, name, email, body, status, ip_hash, user_agent |
| media | uploader_id, disk, path, filename, mime_type, size, width, height, alt_text, caption, credit |
| pages | title, slug, body, status, seo fields, published_at |
| menus / menu_items | location, label, url/route, parent_id, order, target, is_active |
| advertisements | placement, title, image, destination_url, starts_at, ends_at, is_active, impression/click counts |
| settings | group, key, value JSONB, is_public |
| article_views | article_id, session_hash, ip_hash, viewed_on, user_agent |
| audit_logs | actor_id, event, subject_type/id, old_values JSONB, new_values JSONB, ip, user_agent, created_at |

Indeks utama: slug unik; `(status, published_at)`; `(category_id, status, published_at)`; `(is_headline, status)`; GIN full-text title/excerpt/body; indeks parsial untuk artikel published. Foreign key memakai strategi restrict/cascade yang eksplisit dan data editorial memakai soft delete.

## 6. Workflow Editorial

```text
Reporter membuat draft
        ↓
Kirim untuk review ──→ Editor meminta revisi ──→ Draft
        ↓
Editor menyetujui
   ┌────┴─────┐
Terbit kini   Jadwalkan ──→ Scheduler menerbitkan
   └────┬─────┘
     Published ──→ Archived
```

Aturan:

- Hanya artikel `published` dengan `published_at <= now()` tampil di publik.
- Slug stabil setelah terbit; perubahan judul tidak memutus URL lama.
- Perubahan artikel terbit membuat revision dan audit log.
- Reporter tidak dapat menerbitkan atau mengubah artikel milik reporter lain.

## 7. Sistem Desain

### Arah visual

Karakter: cepat, aktual, editorial, bersih, dan padat terkontrol. Layout memakai container maksimum 1280px, grid modular, radius medium, garis pemisah halus, dan whitespace 4/8px. Ciri khas produk adalah strip **Radar Terkini** tepat di bawah navigasi dengan indikator pulsa oranye dan kontrol pause yang aksesibel.

### Token warna

| Token | Light | Dark |
|---|---:|---:|
| brand-primary | `#F15A24` | `#FF7A3D` |
| brand-hover | `#D94712` | `#FF9566` |
| canvas | `#F7F8FA` | `#0B0D10` |
| surface | `#FFFFFF` | `#14171C` |
| surface-muted | `#F1F3F5` | `#1C2026` |
| text-primary | `#17191C` | `#F7F8FA` |
| text-secondary | `#5F6670` | `#B3BAC4` |
| border | `#E1E5EA` | `#303640` |
| success | `#15803D` | `#4ADE80` |
| danger | `#B42318` | `#FB7185` |

Tipografi:

- Display/headline: **Newsreader**, dipakai terbatas untuk headline utama dan judul artikel.
- UI/body: **Roboto**, 16px minimum untuk isi utama, line-height 1.6–1.75.
- Angka/data: Roboto tabular figures.

### Pola UI

- Desktop: utility bar, brand/search row, sticky category nav, breaking strip, 8-column content + 4-column sidebar.
- Mobile: compact header, horizontal category rail, single-column feed, navigasi admin menjadi drawer.
- Touch target minimum 44×44px; focus ring 3px; kontras WCAG AA.
- AOS hanya untuk reveal section penting, maksimal 300ms, dinonaktifkan saat `prefers-reduced-motion`.
- Ikon memakai satu keluarga SVG; tidak memakai emoji sebagai ikon struktural.

## 8. Komponen Teknis

- **Livewire**: pencarian, filter, pagination, form artikel, dashboard, toggle status, moderasi.
- **Alpine.js**: menu mobile, theme switcher, dropdown ringan.
- **Swiper**: carousel hero/berita pilihan, keyboard control, pagination, autoplay yang bisa dihentikan.
- **Tom Select**: select kompleks pada admin; dibungkus `wire:ignore` dan disinkronkan lewat event Livewire.
- **AOS**: progressive enhancement; konten tetap terlihat tanpa JavaScript.
- **Tailwind**: semantic component classes dan CSS variables untuk tema.
- **PostgreSQL**: JSONB settings/audit dan full-text search.

## 9. Keamanan dan Integritas

- CSRF, output escaping, sanitasi HTML artikel berbasis allowlist.
- Form Request/Livewire validation di server.
- Policy pada setiap operasi admin; rate limit login, pencarian, komentar, dan counter.
- Upload divalidasi dari MIME nyata, ukuran, dimensi; nama file acak; SVG tidak diterima secara default.
- Password hashing bawaan Laravel, session regeneration, secure cookie di production.
- Tidak ada secret di JavaScript/public env; audit actor/IP/User-Agent dipertahankan.
- Backup PostgreSQL dan media, retention terukur, serta restore drill berkala.

## 10. Kinerja dan Operasional

- Eager loading untuk mencegah N+1; cache menu, setting, kanal, dan blok homepage.
- Queue untuk optimasi gambar, notifikasi, sitemap, dan publikasi terjadwal.
- WebP/AVIF, `srcset`, lazy loading, dimensi eksplisit untuk mencegah CLS.
- Pagination berbasis cursor untuk feed besar; pagination bernomor untuk hasil pencarian/admin.
- Health check aplikasi/database/cache/queue; structured logging dan error tracking.

## 11. Strategi Pengujian

- Feature: akses per peran, CRUD, workflow, scheduled publish, public visibility, search, komentar.
- Security: IDOR, mass assignment, upload berbahaya, XSS isi artikel, rate limiting.
- Livewire: filter, pagination, validasi, bulk action, theme persistence.
- Unit: slug, status transition, query scope, SEO metadata.
- Browser smoke: homepage, detail, mobile navigation, carousel, Tom Select, AOS, light/dark.
- Gate rilis: PHPUnit/Pest hijau, Pint lolos, Vite build lolos, route/migration check lolos.

## 12. Tahapan Implementasi

### Fase 1 — Fondasi dan vertical slice

- Scaffold Laravel + PostgreSQL config, Livewire, Tailwind/Vite.
- Auth, role sederhana, model/migration/factory/seed inti.
- Portal publik: homepage, kanal, detail, pencarian.
- Admin: dashboard dan CRUD artikel/kategori/tag.
- Tema light/dark, Swiper, Tom Select, AOS.
- Test workflow inti dan akses admin.

### Fase 2 — Workflow redaksi lengkap

- Review/revision, autosave, scheduling, media library, audit log.
- Komentar dan moderasi, halaman/menu, iklan.
- Search PostgreSQL, analytics, cache, queue.

### Fase 3 — Hardening dan peluncuran

- SEO lengkap, optimasi gambar, accessibility audit, browser/E2E test.
- Backup/restore, observability, deployment, performance budget.

## 13. Definition of Done v1

- Fresh install dapat dijalankan dengan `.env`, migrate, seed, dan build assets.
- Akun admin dapat login dan mengelola berita, kategori, serta tag.
- Hanya berita published tampil pada portal publik.
- Homepage, kanal, pencarian, dan detail responsif pada 375/768/1024/1440px.
- Theme preference tersimpan dan tidak mengalami flash yang mengganggu.
- Carousel, Tom Select, dan AOS berfungsi serta memiliki fallback aksesibel.
- Test inti, formatter, dan production asset build lulus.
