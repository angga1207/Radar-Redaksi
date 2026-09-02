<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake('id_ID')->unique()->sentence(fake()->numberBetween(6, 11));

        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'excerpt' => fake('id_ID')->paragraph(2),
            'body' => collect(fake('id_ID')->paragraphs(5))->map(fn (string $paragraph): string => "<p>{$paragraph}</p>")->implode(''),
            'status' => ArticleStatus::Published,
            'featured_image' => 'https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=80',
            'image_alt' => 'Ilustrasi ruang redaksi dan berita terkini',
            'published_at' => fake()->dateTimeBetween('-14 days', 'now'),
            'views_count' => fake()->numberBetween(50, 15000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ArticleStatus::Draft, 'published_at' => null]);
    }
}
