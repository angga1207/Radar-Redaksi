<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4), 'slug' => fake()->unique()->slug(),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>', 'status' => 'published', 'published_at' => now(),
        ];
    }
}
