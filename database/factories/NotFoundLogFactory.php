<?php

namespace Database\Factories;

use App\Models\NotFoundLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotFoundLog>
 */
class NotFoundLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => '/'.fake()->unique()->slug(3),
            'hits' => fake()->numberBetween(1, 50),
            'last_seen_at' => now(),
            'is_ignored' => false,
        ];
    }
}
