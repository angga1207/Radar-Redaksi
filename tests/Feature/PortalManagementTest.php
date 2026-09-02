<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdvertisementManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\SettingsManager;
use App\Livewire\Admin\TaxonomyForm;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_a_public_menu(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($admin)->test(MenuManager::class)
            ->set('name', 'Navigasi Utama')
            ->set('location', 'header')
            ->call('saveMenu')
            ->assertHasNoErrors();

        $menu = Menu::query()->where('name', 'Navigasi Utama')->firstOrFail();
        $component->set('label', 'Tentang Portal')
            ->set('url', '/halaman/tentang')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tentang Portal')
            ->assertSee('/halaman/tentang', false);
        $this->assertDatabaseHas('menu_items', ['menu_id' => $menu->id, 'label' => 'Tentang Portal']);
    }

    public function test_admin_can_create_nested_categories_and_public_submenus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parentCategory = Category::factory()->create(['name' => 'Nasional']);

        Livewire::actingAs($admin)->test(TaxonomyForm::class)
            ->set('name', 'Politik')
            ->set('parentId', (string) $parentCategory->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.taxonomy.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Politik', 'parent_id' => $parentCategory->id]);

        $component = Livewire::actingAs($admin)->test(MenuManager::class)
            ->set('name', 'Navigasi Bertingkat')
            ->set('location', 'header')
            ->call('saveMenu');
        $component->set('label', 'Profil')->set('url', '/profil')->call('saveItem')->assertHasNoErrors();
        $parentItem = Menu::query()->where('name', 'Navigasi Bertingkat')->firstOrFail()->items()->where('label', 'Profil')->firstOrFail();
        $component->set('label', 'Redaksi')->set('url', '/redaksi')->set('parentId', (string) $parentItem->id)->call('saveItem')->assertHasNoErrors();

        $this->assertDatabaseHas('menu_items', ['label' => 'Redaksi', 'parent_id' => $parentItem->id]);
        $this->get(route('home'))->assertOk()->assertSee('Profil')->assertSee('Redaksi');
    }

    public function test_taxonomy_list_and_category_tag_forms_have_separate_urls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Ekonomi']);

        $this->actingAs($admin)->get(route('admin.taxonomy.index'))
            ->assertOk()
            ->assertSee('Kanal dan tag')
            ->assertDontSee('wire:submit="save"', false);
        $this->actingAs($admin)->get(route('admin.taxonomy.categories.create'))
            ->assertOk()
            ->assertSee('Tambah kanal')
            ->assertSee('wire:submit="save"', false);
        $this->actingAs($admin)->get(route('admin.taxonomy.categories.edit', $category))
            ->assertOk()
            ->assertSee('Edit kanal')
            ->assertSee('Ekonomi');
        $this->actingAs($admin)->get(route('admin.taxonomy.tags.create'))
            ->assertOk()
            ->assertSee('Tambah tag')
            ->assertDontSee('Kanal induk');
    }

    public function test_category_parent_cannot_create_a_hierarchy_cycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Category::factory()->create(['name' => 'Induk', 'parent_id' => null]);
        $child = Category::factory()->create(['name' => 'Anak', 'parent_id' => $parent->id]);

        Livewire::actingAs($admin)->test(TaxonomyForm::class, ['category' => $parent])
            ->set('parentId', (string) $child->id)
            ->call('save')
            ->assertHasErrors('parentId');

        $this->assertDatabaseHas('categories', ['id' => $parent->id, 'parent_id' => null]);
    }

    public function test_super_admin_can_upload_public_logo_and_favicon(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)->test(SettingsManager::class)
            ->set('contactEmail', 'redaksi@example.test')
            ->set('facebook', 'https://facebook.com/radar-redaksi')
            ->set('siteLogoUpload', UploadedFile::fake()->image('logo.png', 600, 160))
            ->set('siteFaviconUpload', UploadedFile::fake()->image('favicon.png', 128, 128))
            ->call('save')
            ->assertHasNoErrors();

        $logo = Setting::query()->where('key', 'site_logo')->value('value');
        $favicon = Setting::query()->where('key', 'site_favicon')->value('value');
        Storage::disk('public')->assertExists(str($logo)->after('/storage/')->toString());
        Storage::disk('public')->assertExists(str($favicon)->after('/storage/')->toString());
        $this->get(route('home'))->assertOk()
            ->assertSee($logo, false)
            ->assertSee($favicon, false)
            ->assertSee('redaksi@example.test')
            ->assertSee('https://facebook.com/radar-redaksi', false);
    }

    public function test_admin_can_schedule_an_advertisement_and_only_active_window_is_public(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(AdvertisementManager::class)
            ->set('title', 'Kampanye Aktif')
            ->set('imageUpload', UploadedFile::fake()->image('banner.jpg', 1200, 300))
            ->set('destinationUrl', 'https://example.test/promo')
            ->set('startsAt', now()->subHour()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        Advertisement::factory()->create([
            'title' => 'Kampanye Kedaluwarsa',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kampanye Aktif')
            ->assertDontSee('Kampanye Kedaluwarsa');
    }

    public function test_reporter_cannot_manage_menus_or_advertisements(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);

        Livewire::actingAs($reporter)->test(MenuManager::class)->assertForbidden();
        Livewire::actingAs($reporter)->test(AdvertisementManager::class)->assertForbidden();
        $this->actingAs($reporter)->get(route('admin.menus'))->assertForbidden();
        $this->actingAs($reporter)->get(route('admin.advertisements'))->assertForbidden();
    }
}
