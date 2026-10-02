<?php

namespace Database\Factories\Concerns;

use App\Enums\ContentStatus;
use DateTimeInterface;

/**
 * Draft / scheduled / published states for factories of models that use
 * App\Concerns\HasPublication. Factories default to published.
 */
trait PublicationStates
{
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function scheduled(?DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Scheduled,
            'published_at' => $at ?? now()->addDay(),
        ]);
    }

    public function published(?DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
            'published_at' => $at ?? now()->subDay(),
        ]);
    }
}
