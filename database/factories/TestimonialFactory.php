<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'author_role' => fake()->jobTitle(),
            'company' => fake()->company(),
            'quote' => fake()->paragraph(),
            'rating' => 5,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_visible' => false]);
    }
}
