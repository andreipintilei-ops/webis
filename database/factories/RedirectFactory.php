<?php

namespace Database\Factories;

use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_path' => '/'.fake()->unique()->slug(3),
            'match_type' => RedirectMatchType::Exact,
            'target' => '/'.fake()->slug(2),
            'status_code' => RedirectCode::Permanent,
        ];
    }

    public function gone(): static
    {
        return $this->state(fn (array $attributes) => [
            'target' => null,
            'status_code' => RedirectCode::Gone,
        ]);
    }

    public function prefix(): static
    {
        return $this->state(fn (array $attributes) => ['match_type' => RedirectMatchType::Prefix]);
    }
}
