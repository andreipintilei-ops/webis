<?php

namespace Database\Factories;

use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectCategory>
 */
class ProjectCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2, false), '.');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
