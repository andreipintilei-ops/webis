<?php

namespace Database\Factories;

use App\Enums\ClientType;
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
            'type' => ClientType::Company,
            'products' => null,
            'show_in_logos' => true,
            'show_in_marquee' => true,
            'sort_order' => 0,
        ];
    }

    public function institution(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ClientType::Institution]);
    }

    public function university(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ClientType::University]);
    }
}
