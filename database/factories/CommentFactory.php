<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(), 'name' => fake()->name(), 'email' => fake()->safeEmail(),
            'body' => fake()->paragraph(), 'status' => 'pending', 'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }
}
