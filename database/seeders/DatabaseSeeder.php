<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::factory()->create([
            'name' => 'Admin Radar',
            'username' => 'admin',
            'email' => 'admin@radarredaksi.test',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $reporters = User::factory(5)->create();
        $categories = collect(['Nasional', 'Politik', 'Ekonomi', 'Teknologi', 'Olahraga', 'Gaya Hidup'])
            ->map(fn (string $name, int $index): Category => Category::query()->create([
                'name' => $name,
                'slug' => str($name)->slug(),
                'description' => "Berita {$name} terbaru, faktual, dan terverifikasi.",
                'order' => $index,
            ]));
        $tags = Tag::factory(12)->create();

        Article::factory(30)->recycle($reporters)->recycle($categories)->create()
            ->each(fn (Article $article) => $article->tags()->sync($tags->random(2)->modelKeys()));
        Article::factory(4)->draft()->recycle($admin)->recycle($categories)->create();

        Article::query()->published()->latest('published_at')->limit(5)->update(['is_breaking' => true]);
        Article::query()->published()->latest('views_count')->limit(4)->update(['is_featured' => true]);
        Article::query()->published()->latest('published_at')->limit(1)->update(['is_headline' => true]);

        collect(['Tentang Kami' => 'tentang', 'Redaksi' => 'redaksi', 'Pedoman Media Siber' => 'pedoman-media-siber', 'Kebijakan Privasi' => 'privasi', 'Kontak' => 'kontak'])->each(fn (string $slug, string $title) => Page::query()->create(['title' => $title, 'slug' => $slug, 'body' => "<p>Halaman {$title} Radar Redaksi. Silakan sesuaikan konten ini melalui pengelolaan halaman.</p>", 'status' => 'published', 'published_at' => now()]));

        $footerMenu = Menu::query()->create(['name' => 'Informasi Footer', 'location' => 'footer']);
        $footerMenu->items()->createMany([
            ['label' => 'Tentang Kami', 'url' => '/halaman/tentang', 'order' => 1],
            ['label' => 'Pedoman Media Siber', 'url' => '/halaman/pedoman-media-siber', 'order' => 2],
            ['label' => 'Kontak Redaksi', 'url' => '/halaman/kontak', 'order' => 3],
        ]);
    }
}
