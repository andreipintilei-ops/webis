<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    use PublicationStates;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3, false), '.');

        return [
            'type' => PageType::Page,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(12),
            'blocks' => [],
            'sort_order' => 0,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }

    public function ofType(PageType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }
}
