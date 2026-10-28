<?php

namespace App\Blocks;

class TestimonialsBlock extends Block
{
    public static function type(): string
    {
        return 'testimonials';
    }

    public static function label(): string
    {
        return 'Testimoniale';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'source' => 'all',
            'testimonial_ids' => [],
            'limit' => 6,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}source" => $this->oneOf(['all', 'manual']),
            ...$this->picked($p, 'testimonial_ids', 'testimonials', 12),
            "{$p}limit" => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
