<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploader_id' => User::factory(), 'disk' => 'public', 'path' => 'media/'.fake()->uuid().'.jpg', 'filename' => 'gambar.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'width' => 1200, 'height' => 675, 'alt_text' => fake()->sentence(),
        ];
    }
}
