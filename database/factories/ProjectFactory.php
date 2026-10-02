<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Project;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    use PublicationStates;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(2, false), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'year' => fake()->numberBetween(2015, (int) now()->format('Y')),
            'url' => 'https://'.fake()->domainName(),
            'summary' => fake()->paragraph(),
            'blocks' => [],
            'metrics' => [],
            'is_featured' => false,
            'sort_order' => 0,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }
}
