<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'sector' => fake()->randomElement(['Retail', 'Medical', 'Construcții', 'Educație', 'HoReCa']),
            'url' => 'https://'.fake()->domainName(),
            'is_institution' => false,
            'show_in_logos' => true,
            'sort_order' => 0,
        ];
    }

    public function institution(): static
    {
        return $this->state(fn (array $attributes) => ['is_institution' => true]);
    }
}
