<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_only_published_articles_are_publicly_visible(): void
    {
        $category = Category::factory()->create();
        $published = Article::factory()->for($category)->create();
        $draft = Article::factory()->draft()->for($category)->create();

        $this->get(route('articles.show', $published))->assertOk()->assertSee($published->title);
        $this->get(route('articles.show', $draft))->assertNotFound();
    }

    public function test_reporter_can_open_the_editorial_panel(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $this->actingAs($reporter)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_open_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }
}
