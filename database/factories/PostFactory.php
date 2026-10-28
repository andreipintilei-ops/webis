<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Post;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    use PublicationStates;

    public function definition(): array
    {
        $title = Str::ucfirst(fake()->unique()->sentence(5, false));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(20),
            // body_html, toc and reading_minutes are derived on save.
            'body' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => fake()->paragraph()]]],
                ],
            ],
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }
}
