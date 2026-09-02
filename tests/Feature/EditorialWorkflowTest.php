<?php

namespace Tests\Feature;

use App\Actions\SaveArticleAction;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reporter_can_edit_own_draft_but_not_another_reporters_draft(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $other = User::factory()->create(['role' => 'reporter']);
        $ownDraft = Article::factory()->draft()->for($reporter, 'author')->create();
        $otherDraft = Article::factory()->draft()->for($other, 'author')->create();

        $this->assertTrue($reporter->can('update', $ownDraft));
        $this->assertFalse($reporter->can('update', $otherDraft));
    }

    public function test_editing_published_article_creates_revision_and_status_history(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $article = Article::factory()->create(['title' => 'Judul lama']);
        $attributes = $article->only(['author_id', 'category_id', 'slug', 'excerpt', 'body']);
        $attributes['title'] = 'Judul baru';
        $attributes['status'] = ArticleStatus::Archived;

        app(SaveArticleAction::class)->execute($article, $editor, $attributes, [], 'Arsip koreksi');

        $this->assertDatabaseHas('article_revisions', ['article_id' => $article->id, 'title' => 'Judul lama']);
        $this->assertDatabaseHas('article_status_histories', ['article_id' => $article->id, 'from_status' => 'published', 'to_status' => 'archived']);
    }

    public function test_scheduler_publishes_due_article_but_keeps_future_article_scheduled(): void
    {
        $category = Category::factory()->create();
        $due = Article::factory()->for($category)->create(['status' => ArticleStatus::Scheduled, 'published_at' => null, 'scheduled_at' => now()->subMinute()]);
        $future = Article::factory()->for($category)->create(['status' => ArticleStatus::Scheduled, 'published_at' => null, 'scheduled_at' => now()->addHour()]);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame(ArticleStatus::Published, $due->refresh()->status);
        $this->assertSame(ArticleStatus::Scheduled, $future->refresh()->status);
    }
}
