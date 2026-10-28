<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * Plain assets have no file attached — enough for relation and usage tests.
 * Use ->withImage() when a test needs a real file in the media library.
 *
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'alt' => fake()->sentence(6),
            'width' => 1200,
            'height' => 800,
        ];
    }

    public function withImage(int $width = 1200, int $height = 800): static
    {
        return $this
            ->state(fn (array $attributes) => ['width' => $width, 'height' => $height])
            ->afterCreating(function (Asset $asset) use ($width, $height): void {
                $asset->addMedia(UploadedFile::fake()->image('imagine.jpg', $width, $height))
                    ->toMediaCollection(Asset::COLLECTION);
            });
    }
}
