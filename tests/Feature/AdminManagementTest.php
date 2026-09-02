<?php

namespace Tests\Feature;

use App\Livewire\Admin\ArticleForm;
use App\Livewire\Admin\ArticleIndex;
use App\Livewire\Admin\MediaLibrary;
use App\Livewire\Admin\PageForm;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserManager;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_create_editor_and_audit_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)->test(UserForm::class)->set('name', 'Editor Baru')->set('username', 'editor-baru')->set('email', 'editor@example.test')->set('role', 'editor')->set('password', 'password123')->call('save')->assertHasNoErrors()->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['username' => 'editor-baru', 'role' => 'editor']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.created', 'actor_id' => $admin->id]);
    }

    public function test_super_admin_can_save_author_profile_and_avatar(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)->test(UserForm::class)
            ->set('name', 'Penulis Profil')
            ->set('username', 'penulis-profil')
            ->set('email', 'profil@example.test')
            ->set('role', 'reporter')
            ->set('password', 'password123')
            ->set('bio', 'Reporter investigasi dan isu publik.')
            ->set('avatarUpload', UploadedFile::fake()->image('avatar.jpg', 400, 400))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $author = User::query()->where('username', 'penulis-profil')->firstOrFail();
        $this->assertSame('Reporter investigasi dan isu publik.', $author->bio);
        Storage::disk('public')->assertExists(str($author->avatar)->after('/storage/')->toString());
        $this->get(route('authors.show', $author))->assertOk()->assertSee($author->bio)->assertSee($author->avatar, false);
    }

    public function test_editor_cannot_manage_users(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        Livewire::actingAs($editor)->test(UserManager::class)->assertForbidden();
        Livewire::actingAs($editor)->test(UserForm::class)->assertForbidden();
    }

    public function test_user_list_and_create_edit_forms_have_separate_urls(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $editor = User::factory()->create(['role' => 'editor', 'name' => 'Editor Dinamis']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Daftar pengguna')
            ->assertDontSee('wire:submit="save"', false);
        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah pengguna')
            ->assertSee('wire:submit="save"', false);
        $this->actingAs($admin)->get(route('admin.users.edit', $editor))
            ->assertOk()
            ->assertSee('Edit pengguna')
            ->assertSee('Editor Dinamis');
    }

    public function test_admin_can_publish_static_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(PageForm::class)->set('title', 'Profil Perusahaan')->set('slug', 'profil-perusahaan')->set('body', '<p>Informasi profil perusahaan yang lengkap.</p>')->set('status', 'published')->call('save')->assertHasNoErrors()->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', ['slug' => 'profil-perusahaan', 'status' => 'published']);
        $page = Page::query()->where('slug', 'profil-perusahaan')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk()->assertSeeLivewire(PageForm::class);
        $this->actingAs($admin)->get(route('admin.pages.edit', $page))->assertOk()->assertSee('Edit halaman');
    }

    public function test_admin_can_save_an_article_and_is_redirected_to_the_index(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Livewire::actingAs($admin)->test(ArticleForm::class)
            ->set('title', 'Berita Uji Simpan Artikel')
            ->set('excerpt', 'Ringkasan berita yang cukup jelas untuk pembaca portal.')
            ->set('body', '<p>Isi berita pengujian yang panjangnya lebih dari lima puluh karakter agar lolos validasi.</p>')
            ->set('categoryId', (string) $category->id)
            ->set('featuredImageUpload', UploadedFile::fake()->image('headline.webp', 1200, 675))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'slug' => 'berita-uji-simpan-artikel',
            'author_id' => $admin->id,
            'status' => 'draft',
        ]);
        $article = Article::query()->where('slug', 'berita-uji-simpan-artikel')->firstOrFail();
        Storage::disk('public')->assertExists(str($article->featured_image)->after('/storage/')->toString());
    }

    public function test_admin_article_search_is_case_insensitive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matching = Article::factory()->create(['title' => 'Transformasi DIGITAL Redaksi']);
        $unrelated = Article::factory()->create(['title' => 'Olahraga Daerah']);

        Livewire::actingAs($admin)->test(ArticleIndex::class)
            ->set('search', 'digital')
            ->assertSee($matching->title)
            ->assertDontSee($unrelated->title)
            ->set('search', 'DIGITAL')
            ->assertSee($matching->title)
            ->assertDontSee($unrelated->title);
    }

    public function test_admin_sidebar_is_grouped_and_scrollable(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-sidebar-scroll', false)
            ->assertSee('Konten portal')
            ->assertSee('Sistem')
            ->assertSee('overflow-y-auto', false);
    }

    public function test_reporter_can_upload_valid_image(): void
    {
        Storage::fake('public');
        $reporter = User::factory()->create(['role' => 'reporter']);

        Livewire::actingAs($reporter)->test(MediaLibrary::class)
            ->call('create')
            ->assertSee('wire:submit="saveMedia"', false)
            ->set('file', UploadedFile::fake()->image('liputan-lapangan.jpg', 1200, 675))
            ->call('saveMedia')
            ->assertHasNoErrors()
            ->assertSee('Gambar berhasil ditambahkan');

        $this->assertDatabaseHas('media', ['filename' => 'liputan-lapangan.jpg', 'alt_text' => 'Liputan Lapangan', 'uploader_id' => $reporter->id]);

        $media = Media::query()->firstOrFail();
        Livewire::actingAs($reporter)->test(MediaLibrary::class)->call('edit', $media->id)->assertSet('showModal', true)->set('altText', 'Liputan lapangan terbaru')->call('saveMedia')->assertHasNoErrors()->assertSet('showModal', false);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'alt_text' => 'Liputan lapangan terbaru']);
    }

    public function test_article_and_page_forms_render_enhanced_editors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('data-tom-select', false)
            ->assertSee('data-livewire-property="tagIds"', false)
            ->assertSee('data-image-upload-url=', false)
            ->assertSee('type="file"', false)
            ->assertDontSee('id="slug"', false)
            ->assertSee('<trix-editor', false);

        $this->actingAs($admin)->get(route('admin.pages.create'))
            ->assertOk()
            ->assertSee('<trix-editor', false)
            ->assertDontSee('id="page-slug"', false)
            ->assertSee('data-livewire-property="body"', false)
            ->assertDontSee('data-image-upload-url=', false);
    }
}
