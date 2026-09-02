<?php

namespace Tests\Feature;

use App\Livewire\Admin\ArticleForm;
use App\Livewire\Admin\CommentManager;
use App\Livewire\Admin\RoleManager;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FeatureCompletenessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_multimedia_and_direct_static_page_routes_are_public(): void
    {
        $photo = Article::factory()->create(['content_type' => 'photo']);
        $video = Article::factory()->create(['content_type' => 'video']);
        $page = Page::query()->create(['title' => 'Tentang', 'slug' => 'tentang', 'body' => '<p>Tentang portal.</p>', 'status' => 'published', 'published_at' => now()]);

        $this->get(route('articles.photos'))->assertSee($photo->title)->assertDontSee($video->title);
        $this->get(route('articles.videos'))->assertSee($video->title)->assertDontSee($photo->title);
        $this->get(route('pages.direct', $page))->assertSee('Tentang portal');
    }

    public function test_editor_can_preview_a_draft_and_load_its_revision(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $article = Article::factory()->draft()->create();
        $revision = $article->revisions()->create(['user_id' => $editor->id, 'title' => 'Judul revisi', 'excerpt' => 'Ringkasan revisi', 'body' => '<p>Isi revisi yang dapat dipulihkan.</p>']);

        $this->actingAs($editor)->get(route('admin.articles.preview', $article))->assertSee('Mode pratinjau')->assertSee($article->title);
        Livewire::actingAs($editor)->test(ArticleForm::class, ['article' => $article])->call('loadRevision', $revision->id)->assertSet('title', 'Judul revisi');
    }

    public function test_advertisement_impressions_and_clicks_are_tracked(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('advertisements/banner.jpg', 'image');
        $advertisement = Advertisement::factory()->create(['image_url' => Storage::disk('public')->url('advertisements/banner.jpg'), 'destination_url' => 'https://example.test/promo']);

        $this->get(route('advertisements.image', $advertisement))->assertOk();
        $this->get(route('advertisements.click', $advertisement))->assertRedirect('https://example.test/promo');

        $this->assertDatabaseHas('advertisements', ['id' => $advertisement->id, 'impressions_count' => 1, 'clicks_count' => 1]);
    }

    public function test_rich_text_sanitizer_removes_scripts_and_unsafe_attributes(): void
    {
        $clean = HtmlSanitizer::clean('<p onclick="alert(1)">Aman</p><script>alert(1)</script><a href="javascript:alert(1)">tautan</a>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<p>Aman</p>', $clean);
    }

    public function test_article_body_image_upload_is_authorized_validated_and_sanitized(): void
    {
        Storage::fake('public');
        $reporter = User::factory()->create(['role' => 'reporter']);

        $response = $this->actingAs($reporter)->postJson(route('admin.articles.body-images.store'), [
            'image' => UploadedFile::fake()->image('gambar-isi.jpg', 1200, 800),
        ]);

        $response->assertCreated()->assertJsonStructure(['url', 'href', 'contentType']);
        $path = str($response->json('url'))->after('/storage/')->toString();
        Storage::disk('public')->assertExists($path);
        $clean = HtmlSanitizer::clean('<figure onclick="alert(1)"><img src="'.$response->json('url').'" onerror="alert(1)" alt="Liputan"></figure><img src="https://evil.test/tracker.jpg">');
        $this->assertStringContainsString($response->json('url'), $clean);
        $this->assertStringContainsString('alt="Liputan"', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('evil.test', $clean);
    }

    public function test_article_body_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $reporter = User::factory()->create(['role' => 'reporter']);

        $this->actingAs($reporter)->postJson(route('admin.articles.body-images.store'), [
            'image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');

        Storage::disk('public')->assertDirectoryEmpty('article-body');
    }

    public function test_role_permissions_control_admin_modules(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($superAdmin)->test(RoleManager::class);
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $component->call('edit', $adminRole->id)->set('permissions', ['articles.manage'])->call('save')->assertHasNoErrors();

        Livewire::actingAs($admin)->test(CommentManager::class)->assertForbidden();
    }

    public function test_health_endpoint_reports_database_and_cache(): void
    {
        $this->get(route('health'))->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('checks.database', true)->assertJsonPath('checks.cache', true);
    }

    public function test_super_admin_wildcard_permission_can_be_saved(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $component = Livewire::actingAs($superAdmin)->test(RoleManager::class);
        $role = Role::query()->where('name', 'super_admin')->firstOrFail();

        $component->call('edit', $role->id)->set('label', 'Super Admin Utama')->set('permissions', ['*'])->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'label' => 'Super Admin Utama']);
    }

    public function test_homepage_exposes_accessible_carousel_pause_controls(): void
    {
        Article::factory(3)->create(['is_breaking' => true, 'is_featured' => true]);

        $this->get(route('home'))->assertSee('data-carousel-toggle="breaking"', false)->assertSee('data-carousel-toggle="hero"', false)->assertSee('aria-pressed="false"', false);
    }

    public function test_public_pages_keep_aos_content_visible_before_javascript_initializes(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-public-shell', false);

        $stylesheet = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString('[data-public-shell] [data-aos]:not(.aos-init)', $stylesheet);
    }
}
