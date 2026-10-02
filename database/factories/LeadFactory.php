<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '07'.fake()->numerify('########'),
            'budget' => null,
            'message' => fake()->paragraph(),
            'consent_at' => now(),
            'status' => LeadStatus::New,
        ];
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LeadStatus::Spam]);
    }
}
