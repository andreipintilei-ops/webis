<?php

namespace App\Blocks;

/**
 * Big numbers with a label: "250+ site-uri livrate".
 */
class StatsBlock extends Block
{
    public static function type(): string
    {
        return 'stats';
    }

    public static function label(): string
    {
        return 'Cifre';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'items' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}items" => ['required', 'array', 'min:1', 'max:6'],
            "{$p}items.*" => ['array:value,label,note'],
            "{$p}items.*.value" => $this->text(true, 20),
            "{$p}items.*.label" => $this->text(true, 80),
            "{$p}items.*.note" => $this->text(false, 160),
        ];
    }
}
