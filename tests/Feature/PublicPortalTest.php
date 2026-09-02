<?php

namespace Tests\Feature;

use App\Livewire\ArticleComments;
use App\Livewire\NewsSearch;
use App\Models\Article;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_article_view_is_counted_once_per_session_per_day(): void
    {
        $article = Article::factory()->create(['views_count' => 10]);

        $this->get(route('articles.show', $article))->assertSee($article->title);
        $this->get(route('articles.show', $article))->assertSee($article->title);

        $this->assertSame(11, $article->refresh()->views_count);
        $this->assertDatabaseCount('article_views', 1);
    }

    public function test_comment_submission_is_queued_for_moderation(): void
    {
        $article = Article::factory()->create(['allow_comments' => true]);

        Livewire::test(ArticleComments::class, ['article' => $article])
            ->set('name', 'Pembaca Radar')->set('email', 'reader@example.test')
            ->set('body', 'Komentar yang layak untuk dimoderasi.')->call('submit')
            ->assertHasNoErrors()->assertSee('menunggu moderasi');

        $this->assertDatabaseHas('comments', ['article_id' => $article->id, 'email' => 'reader@example.test', 'status' => 'pending']);
    }

    public function test_sitemap_contains_only_public_article_urls(): void
    {
        $published = Article::factory()->create();
        $draft = Article::factory()->draft()->create();

        $response = $this->get(route('sitemap'));

        $response->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('articles.show', $published))->assertDontSee(route('articles.show', $draft));
    }

    public function test_public_news_search_is_case_insensitive_and_renders_without_error(): void
    {
        $matching = Article::factory()->create(['title' => 'Ekonomi DIGITAL Indonesia']);
        $unrelated = Article::factory()->create(['title' => 'Olahraga Hari Ini']);

        $this->get(route('search', ['q' => 'digital']))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($unrelated->title);

        Livewire::test(NewsSearch::class)
            ->set('query', 'DIGITAL')
            ->assertSee($matching->title)
            ->assertDontSee($unrelated->title)
            ->set('query', 'digital')
            ->assertSee($matching->title)
            ->assertDontSee($unrelated->title);
    }

    public function test_public_news_search_requires_at_least_two_non_whitespace_characters(): void
    {
        $article = Article::factory()->create(['title' => 'Berita yang tidak boleh muncul']);

        Livewire::test(NewsSearch::class)
            ->set('query', ' A ')
            ->assertDontSee($article->title)
            ->assertDontSee('Berita tidak ditemukan');
    }

    public function test_homepage_renders_breaking_and_featured_carousels_with_valid_slides(): void
    {
        Article::factory()->create(['is_headline' => true]);
        $featured = Article::factory(2)->create(['is_featured' => false, 'is_breaking' => true]);

        $response = $this->get(route('home'));
        $cachedResponse = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('breaking-swiper', false)
            ->assertSee('hero-swiper', false)
            ->assertSee('swiper-wrapper', false)
            ->assertSee('Thumbnail:', false)
            ->assertSee($featured->first()->title)
            ->assertSee($featured->last()->title);
        $cachedResponse->assertOk()->assertSee($featured->first()->title)->assertSee($featured->last()->title);
    }
}
